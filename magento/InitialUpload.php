<?php

/* Copyright (C) Youbility Software - All Rights Reserved
* Unauthorized copying of this file, via any medium is strictly prohibited
* Proprietary and confidential
*/

/**
 * Class Semknox_ProductSearch_Model_InitialUpload.
 *
 * This class is responsible for uploading ALL visible products to semknox. It's doing this in small batches so in shops with many products we don't get a timeout when we're reading all product data.
 */
class Semknox_ProductSearch_Model_InitialUpload
{
	/**
	 * How many products to read from database on each batch
	 */
	const READBATCH_SIZE = 200;

	/**
	 * How many products to send to semknox at once. Must NOT be smaller
	 * than READBATCH_SIZE!
	 */
	const SENDBATCH_SIZE = 1000; //2000; decrease to prevent timeouts on some servers

	/**
	 * The directory we're writing our upload information to.
	 * @var string
	 */
	protected $_dir;

	/**
	 * @var Path to file with upload information
	 */
	protected $_infoFile;

	protected $_infoFileLastCompleteUpload;

	/**
	 * @var Path to file with product data
	 */
	protected $_dataFile;

	/**
	 * @var Semknox_ProductSearch_Model_ProductManager
	 */
	protected $_productManager;

	/**
	 * Total number of available pages
	 * @var
	 */
	protected $_numberOfPages;

	/**
	 * How the upload was started. "cron" or "manual"
	 * @var string
	 */
	protected $_startedby = 'manual';

	/**
	 * How the upload was started. "cron" or "manual"
	 * @var string
	 */
	protected $_pause;

	/**
	 * Current store Id
	 * @var
	 */
	protected $_storeId;

	protected $_helper;

	protected $_fileHandler;

	protected $_sendBatchSizeConfig;
	protected $_readBatchSizeConfig;

	/**
	 * Set paths for files.
	 */
	public function __construct($args)
	{
		if(isset($args[1]))
		{
			$this->_startedby = $args[1];
		}

		if(isset($args[2]))
		{
			$this->_pause = $args[2];
		}

		// store id for initial upload
		$this->_storeId = (int) $args[0];

		// path to /var/semknox/upload-store-1-manual/
		$dir = 'upload-store-' . $this->_storeId . '-' . $this->_startedby;
		$this->_dir = Mage::getBaseDir('var') . DS . 'semknox' . DS . $dir . DS;

		// contains information about the upload process
		$this->_infoFile = $this->_dir . 'upload-info.json';
		$this->_infoFileLastCompleteUpload = $this->_dir . 'last-complete-upload.json';

		// ProductManager instance required for upload
		$this->_productManager = Mage::getSingleton('semknoxps/ProductManager');
		$this->_productManager->setCurrentStoreId($this->_storeId);

		$this->_helper = Mage::helper('semknoxps');

		@$this->_fileHandler = new Varien_Io_File();

		// create semknox and upload directory if it does not exist
		if(! @$this->_fileHandler->fileExists($this->_dir, false) && $this->_storeId)
		{
			@$this->_fileHandler->mkdir($this->_dir, 0777, true); // recursive
		}

		$this->_readBatchSizeConfig = (int) Mage::getStoreConfig('semknoxps/advancedsettings/readBatchSize', $this->_storeId);
		$this->_sendBatchSizeConfig = (int) Mage::getStoreConfig('semknoxps/advancedsettings/sendBatchSize', $this->_storeId);
	}

	/**
	 * Check if the initial upload was already started
	 */
	public function check($full = false)
	{
		$info = $this->_getBatchUploadInfo();

		if($full) return $info;

		return $info['percentage'];
	}

	/**
	 * cancel initial upload
	 */
	public function cancel()
	{
		// save last complete info
		if(@$this->_fileHandler->fileExists($this->_infoFileLastCompleteUpload)){
			@$this->_fileHandler->mv($this->_infoFileLastCompleteUpload,$this->_dir . '../temp');
		}

		$status = @$this->_fileHandler->rmdir($this->_dir,true);

		// create semknox and upload directory if it does not exist
		if(! @$this->_fileHandler->fileExists($this->_dir, false) && $this->_storeId)
		{
			@$this->_fileHandler->mkdir($this->_dir, 0777, true); // recursive
		}

		// restore last complete info
		if(@$this->_fileHandler->fileExists($this->_dir . '../temp')){
			@$this->_fileHandler->mv($this->_dir . '../temp', $this->_infoFileLastCompleteUpload);
		}

		return $status;
	}



	/**
	 * Upload all products to semknox.
	 */
	public function start()
	{
		$info = $this->_getBatchUploadInfo();

		if($info['pause'] == true) return $info;

		// if init call, just save file
		if(isset($info['startNow']))
		{
			unset($info['startNow']);

			$info['status'] = 'collecting';
			$info['percentage'] = 1;
			$this->_saveInfoFile($info);
			$info['startNow'] = true;
			return;
		}


		if(! $info['collectCompleted'])
		{
			if(!$info['lastBatch'])
			{
				$this->_helper->log('InitialUpload ['.$info['id'].']: started by '.strtoupper($this->_startedby), null, false, $this->_storeId);
			}

			$info['lastBatch'] = time();

			$info['status'] = 'collecting';

			// keep collection products
			return $this->_collectProducts($info);
		}
		else
		{
			$data['lastBatch'] = time();

			$info['status'] = 'uploading';

			// if we're done collecting products, send to semknox
			return $this->_uploadProducts($info);
		}
	}

	//--------------------------------------------------------------
	// All methods related to reading and writing the info file
	//--------------------------------------------------------------
	#region INFOFILE

	/**
	 * Return an array with information about the current upload status
	 * [
	 *    "productsCollected" => 0,        // number of products collected so far
	 *    "collectCompleted" => false,     // is collecting all products completed
	 *    "readBatch"        => 1          // which batch we're in for reading from db
	 *    "sendBatch"        => 0          // which batch we're in for uploading products
	 *    "lastBatch"        => 123165454  // timestamp last upload
	 *    "percentage"       => 0          // percent of upload
	 *    "startedBy"        => 'manual'   // started manually or by cron
	 *    "storeId"          => 1          // StoreID
	 * ]
	 */
	protected function _getBatchUploadInfo()
	{
		if(! @$this->_fileHandler->fileExists($this->_infoFile)) {

			$id = substr($this->_startedby,0,1).'-'. substr(time(), -5);

			// return default information
			$info = array(
				'id'				=> $id,
				'productsCollected' => 0,
				'collectCompleted' 	=> false,
				'readBatch'         => 0, // 1
				'sendBatch'         => 0,
				'sendBatchQty'      => 0,
				'lastBatch'        	=> null,
				'percentage'       	=> 0,
				'startedBy'       	=> $this->_startedby,
				'storeId'          	=> $this->_storeId,
				'message'          	=> 'Collecting products...',
				'startTime'			=> time(),
				'status'			=> 'pending',
				'currentStep'		=> 1,
				'stepQty'			=> 3,
				'pause'				=> true,
				'startNow'			=> true // needed to have no timeout on huge first calls
			);

		}
		else {
			// return information in log file
			$info = json_decode(@$this->_fileHandler->read($this->_infoFile), true);

			if(! $info['collectCompleted']) {
				$info['readBatch']++;
			}
			else {
				$info['sendBatch']++;
			}
		}

		// handle info of last complete upload
		if(@$this->_fileHandler->fileExists($this->_infoFileLastCompleteUpload)) {
			$lastinfo = json_decode(@$this->_fileHandler->read($this->_infoFileLastCompleteUpload), true);

			$info['last']['endTime'] = date('d.m.Y H:i', $lastinfo['endTime']);
			$info['last']['startTime'] = date('d.m.Y H:i', $lastinfo['startTime']);
			$info['last']['duration'] = gmdate("H:i:s",$lastinfo['duration']);
			$info['last']['status'] = $lastinfo['status'];
			$info['last']['productsCollected'] = $lastinfo['productsCollected'];
		}

		// check if pause or not
		if(!is_null($this->_pause) || $this->_pause != '')
		{
			$info['pause'] = ($this->_pause == 'true') ? true : false;
			$this->_saveInfoFile($info);
		}

		return $info;
	}


	/**
	 * Save information about current batch in file
	 *
	 * @param $batch
	 * @param $completed
	 */
	protected function _saveInfoFile($data=array())
	{
		//$data['lastBatch'] = time();

		@$this->_fileHandler->write($this->_infoFile, json_encode($data));
	}

	#endregion
	//--------------------------------------------------------------
	// All methods related to collecting products
	//--------------------------------------------------------------
	#region READ

	/**
	 * Start collection products in batches
	 *
	 * @param $info
	 * @return float
	 */
	private function _collectProducts($info) {

		$info['currentStep'] = 1;

		// get products from database
		$products = $this->_readNextProductsFromDb($info['readBatch']);

		// transform magento model to array
		$products = $this->_productManager->transformProducts($products);

		// add products to file
		$info['sendBatchQty'] = $this->_saveProductsInFile($products, $info);

		// number of products collected so far
		$info['productsCollected'] += count($products);

		// is collecting all products completed (boolean)
		$info['collectCompleted'] = ($info['readBatch'] >= $this->_numberOfPages);

		// the current batch upload progress
		$info['percentage'] = round( ($info['readBatch'] / ($this->_numberOfPages + 1)) * 100);

		//time remaining
		$timeLeft = $this->_timeRemaining($info);

		// set interface message
		$message = 'Collecting products... ('.$info['productsCollected'].' products collected; current batch: '.$info['readBatch'].', time left: '.$timeLeft.')';

		$this->_helper->log('InitialUpload ['.$info['id'].'] ['.$info['currentStep'].'/'.$info['stepQty'].']: ' . $message, null, false, $this->_storeId);

		$info['message'] = $message;

		// persist the current information so we can continue from here in the next step
		$this->_saveInfoFile($info);

		//return $info['percentage'];
		return $info;
	}


	/**
	 * Get ReadBatchSize
	 *
	 * @return int
	 */
	protected function getReadBatchSize()
	{
		$readBatchSize = $this->_readBatchSizeConfig;

		if($readBatchSize <= 0 || $readBatchSize > $this->_sendBatchSizeConfig)
		{
			$readBatchSize = self::READBATCH_SIZE;

			Mage::getModel('core/config')->saveConfig('semknoxps/advancedsettings/readBatchSize', $readBatchSize, 'stores', $this->_storeId );

			$this->_helper->log('Incorrect READ-Batch Size -> changed to ' . $readBatchSize, null, false, $this->_storeId);

			$this->_readBatchSizeConfig = $readBatchSize;
		}

		return $readBatchSize;
	}

	/**
	 * Get SendBatchSize
	 *
	 * @return int
	 */
	protected function getSendBatchSize()
	{
		$sendBatchSize = $this->_sendBatchSizeConfig;

		if($sendBatchSize <= 0 || $sendBatchSize < $this->_readBatchSizeConfig)
		{
			$sendBatchSize = self::SENDBATCH_SIZE;

			Mage::getModel('core/config')->saveConfig('semknoxps/advancedsettings/sendBatchSize', $sendBatchSize, 'stores', $this->_storeId );

			$this->_helper->log('Incorrect SEND-Batch Size -> changed to ' . $sendBatchSize, null, false, $this->_storeId);

			$this->_sendBatchSizeConfig = $sendBatchSize;
		}

		return $sendBatchSize;
	}


	/**
	 * Read products from database.
	 * @param int $step
	 *
	 * @return int
	 */
	protected function _readNextProductsFromDb($step=1, $limit=null)
	{
		if($limit == null) {
			$limit = $this->getReadBatchSize();
		}

		$collection = Mage::getModel('catalog/product')
		                  ->getCollection()
		                  ->addStoreFilter($this->_storeId)
		                  ->addAttributeToSelect('*')
                          ->setCurPage($step)
		                  ->setPageSize($limit);

		//$collection->addAttributeToFilter('visibility', array('in'=>Mage::getSingleton('catalog/product_visibility')->getVisibleInSearchIds()));
		// -> pass all products to productManager (configurable's simple products will be passed to semknox even if they are "einzeln nciht sichtbar")

		$collection->addAttributeToFilter('status', array('in'=> Mage::getSingleton('catalog/product_status')->getVisibleStatusIds()));

		// check if products still in stock
		if(!$this->_helper->uploadOutOfStockProducts($this->_storeId)){
			Mage::getSingleton('cataloginventory/stock')->addInStockFilterToCollection($collection);
		}

		// save number of pages
		$this->_numberOfPages = $collection->getLastPageNumber();

		$products = array();

		foreach ($collection as $product) {
			$products[] = $product;
		}

		//$this->_helper->log('INFO: '.count($products).' products,'.$this->_numberOfPages.' pages, limit: '.$limit.', step/readbatch: '.$step, null, false, $this->_storeId);

		return $products;
	}

	/**
	 * Append these products to the file
	 * @param $products
	 */
	protected function _saveProductsInFile($products, $info)
	{
		// if there are no products, do nothing in this batch
		$productsCount = count($products);
		if($productsCount == 0) return;

		$totalProducts = ($info['productsCollected'] + $productsCount);
		$fileIdx = ceil($totalProducts / $this->getSendBatchSize());

		// $this->_fileHandler does not provide a method to use
		// ftruncate so we're using non-Zend functionality here
		// @codingStandardsIgnoreStart
		$handle = @fopen($this->_dataFile($fileIdx), 'a+b');

		// lock for writing
		@flock($handle, LOCK_EX);

		// get information about file
		$stat = @fstat($handle);

		$content = json_encode($products);

		// if more than 15 characters were written in the file
		// (there's at least one product written already)
		// remove the closing "]" from the file, replace the
		// starting [ from our json string with a comma and append
		if($stat['size'] > 15) {
			// remove last character from file
			@ftruncate($handle, $stat['size']-1);

			// replace the starting [ from our json
			// data with a comma to correctly format json
			$content[0] = ',';
		}

		// write content
		@fwrite($handle, $content);


		// unlock file
		@flock($handle, LOCK_UN);

		@fclose($handle);
		// @codingStandardsIgnoreEnd

		return $fileIdx;
	}

	#endregion
	//--------------------------------------------------------------
	// All methods related to uploading products
	//--------------------------------------------------------------
	#region SEND

	/**
	 * Upload all the products to semknox.
	 * @return int
	 */
	private function _uploadProducts($info) {

		$id = $info['id'];

		$info['currentStep'] = 2;

		// first batch, do things that have to be done before uploading the first batch of products
		if($info['sendBatch'] == 1) {
			$this->_doActionsBeforeFirstUploadBatch($info);
		}

		$success = $this->_sendProductBatchToSemknox($info);
		$completed = ($info['sendBatch'] * $this->getSendBatchSize()) >= $info['productsCollected'];

		if($success) {

			$message = 'products (batch '.$info['sendBatch'].') successfully sent to SEMKNOX';

			// log status
			$this->_helper->log('InitialUpload ['.$id.'] ['.$info['currentStep'].'/'.$info['stepQty'].']: '.$message, null, false, $this->_storeId);

			$info['message'] = $message;

		}
		else {

			$message = $this->_productManager->getStatusMessage();
			if(! $message) {
				$message = 'Error uploading to semknox';
			}

			$message = 'FAILED - Could not send products (batch '.$info['sendBatch'].') to semknox. ' . $message;

			$this->_helper->log('InitialUpload ['.$id.'] ['.$info['currentStep'].'/'.$info['stepQty'].']: ' . $message, null, false, $this->_storeId);

			$info['message'] = $message;

			throw new RuntimeException($message);
		}

		if($completed) {

			// do actions after initial upload is done
			$info = $this->_doActionsAfterLastUploadBatch($info);

		} else {

			$percentage = round( ($info['sendBatch'] / $info['sendBatchQty']) * 100);

			// upload is always 99% if not finished
			$info['percentage'] = ($percentage == 100) ? 99 : $percentage;
		}

		// write to info file
		$this->_saveInfoFile($info);

		// clean up files
		$this->_cleanUpFiles($info, $completed);


		return $info;
	}


	/**
	 * Do things that have to be done before the first batch of products gets uploaded
	 * @param array $info
	 */
	private function _doActionsBeforeFirstUploadBatch($info)
	{
		$id = $info['id'];

		$this->_helper->log(
			'InitialUpload ['.$id.'] ['.$info['currentStep'].'/'.$info['stepQty'].']: '
			. $info['productsCollected']
			. ' products successfully collected. Starting upload..', null, false, $this->_storeId);

		$this->_clearSemknoxInput();
	}


	/**
	 * Do things after the last batch was sent to SEMKNOX.
	 *
	 * @param $info
	 */
	private function _doActionsAfterLastUploadBatch(&$info)
	{
		$this->_triggerFullUpdate();

		$id = $info['id'];

		$info['currentStep'] = 3;
		$info['status'] = 'after_product_work';
		$this->_saveInfoFile($info);

		$this->sendQueryLog();

		$this->sendAutosuggestMappings();

		$info['endTime'] = time();
		$duration = $info['endTime'] - $info['startTime'];
		$message = 'sent query log to SEMKNOX successfully; Initial upload completed; duration: '.gmdate("H:i:s", $duration);
		$this->_helper->log('InitialUpload ['.$id.'] ['.$info['currentStep'].'/'.$info['stepQty'].']: '.$message, null, false, $this->_storeId);

		$info['message'] = $message;
		$info['duration'] = $duration;
		$info['percentage'] = 100;
		$info['status'] = 'finished';


		return $info;
	}


	/**
	 * Clear input cache in Semknox API before initial upload.
	 */
	private function _clearSemknoxInput()
	{
		return $this->_productManager->clearInputCache();
	}

	/**
	 * Notify semknox that full update was sent.
	 *
	 * @return mixed
	 */
	private function _triggerFullUpdate()
	{
		return $this->_productManager->fullUpdateSent();
	}

	/**
	 * And a batch of collected products to semknox
	 * @return bool
	 */
	protected function _sendProductBatchToSemknox($info)
	{
		$idx = $info['sendBatch'];

		$products = json_decode(@$this->_fileHandler->read($this->_dataFile($idx)), true);

		$flagFull = true; // we're doing a full update

		return $this->_productManager->sendProductUpdate($products, $flagFull);
	}

	#endregion
	//--------------------------------------------------------------
	// Helper methods
	//--------------------------------------------------------------

	/**
	 * Get path for current product data file
	 * @param int $idx
	 * @return string
	 */
	private function _dataFile($idx=1) {
		return $this->_dir . "upload-data-$idx.json";
	}


	/**
	 * Rename the infoFile and save the uploadData
	 */
	protected function _cleanUpFiles($info, $completed=false)
	{
		$idx = $info['sendBatch'];
		$time = date('Y-m-d_H-i');

		if($completed) {
			// rename and keep infoFile
			$newInfoFilename   = $info['id'].'-completed-upload-info-'. $time . '.json';
			@$this->_fileHandler->mv($this->_infoFile, $this->_dir . $newInfoFilename);

			// keep info file as last upload info
			@$this->_fileHandler->rm($this->_infoFileLastCompleteUpload);
			@$this->_fileHandler->cp($this->_dir . $newInfoFilename, $this->_infoFileLastCompleteUpload);
		}

		// move data file
		$newFilename   = $info['id'].'-completed-upload-data-' . $idx . '-' . $time . '.json';

		@$this->_fileHandler->mv($this->_dataFile($idx), $this->_dir . $newFilename);
	}

	/**
	 * Send query log to semknox.
	 */
	public function sendQueryLog()
	{
		$terms = Mage::getResourceModel('catalogsearch/query_collection')
		             ->setPopularQueryFilter(Mage::app()->getStore()->getId())
		             ->setPageSize(100)
		             ->load()
		             ->getItems();

		if(count($terms) == 0 ) {
			return false;
		}

		$query_data = array();

		/* @var $terms Mage_CatalogSearch_Model_Query */
		foreach ($terms as $term) {
			$data = $term->getData();

			$query_data[] = array(
				'query' => $data['name'],
				"count" => $data['popularity']
			);
		}

		return $this->_productManager->sendQueryLog($query_data);
	}

	/**
	 * Send categories and brands to semknox.
	 */
	public function sendAutosuggestMappings()
	{
		$ccss = array();

		// categories
		$rootId     = Mage::app()->getStore($this->_storeId)->getRootCategoryId();
		$categories = Mage::getModel('catalog/category')
						  ->getCollection()
						  ->addAttributeToFilter('is_active', 1)
						  ->addFieldToFilter('path', array('like'=> "1/$rootId/%"))
						  ->addAttributeToSelect('*');

		foreach($categories as $category) {

			if($category->getUrlPath() == '/') continue;

			$url =  Mage::getBaseUrl('web', true).$category->getUrlPath();

			$type = 'CATEGORY';
			$categoryId = $category->getId();
			$categoryWeight = $category->getLevel();
			$imageUrl = ($category->getThumbnail()) ? Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA).'catalog/category/' . $category->getThumbnail() : null;
			$name = $category->getName();

			$ccss[] = array(
				'type' => $type,
				'categoryId' => $categoryId,
				'categoryWeight' => $categoryWeight,
				'imageUrl' => $imageUrl,
				'url' => $url,
				'name' => $name
			);

		}

		return $this->_productManager->sendAutosuggestMappings($ccss);
	}

	/**
	 * time remaining
	 * at the moment just rudimentary to have any clue how long to wait
	 */
	protected function _timeRemaining($info)
	{
		$startTime = $info['startTime'];

		$batchesDone = $info['readBatch'];
		$batchesToDo = $this->_numberOfPages - $batchesDone + 1;

		$currentDuration = time() - $startTime;
		$timePerBatch = $currentDuration / $batchesDone;

		return '~'. gmdate("H:i:s", $timePerBatch * $batchesToDo);
	}


}