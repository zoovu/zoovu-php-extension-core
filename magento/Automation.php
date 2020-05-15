<?php
/* Copyright (C) Youbility Software - All Rights Reserved
 * www.youbility.de
 * Unauthorized copying of this file, via any medium is strictly prohibited
 * Proprietary and confidential
 */

class Semknox_ProductSearch_Model_Automation
{
    /**
     * The directory in which log files are stored
     * @var string
     */
    protected $_logDir;

    /**
     * @var Semknox_ProductSearch_Helper_Data
     */
    protected $_helper;

    protected $_cronDisabled = false;

    /**
     * @var Varien_Io_File
     */
    protected $_fileHandler;

    protected $_storesCredentials;

    protected $_updatesToSemknoxAtOnce = 80;

    public function __construct()
    {
        $this->_logDir = Mage::getBaseDir('var') . DS . 'semknox' . DS;

        /** @var Semknox_ProductSearch_Helper_Data $helper */
        $this->_helper = Mage::helper('semknoxps');

        $this->_cronDisabled = !$this->_helper->semknoxCronjobIsActive();

        $this->_fileHandler = new Varien_Io_File();

        //get all stores with api credentials
        $this->_storesCredentials = $this->_helper->getAllStoresCredentials();

        //set random Cronjob start if not set
        $this->_setRandomCronjobTime();

    }


    protected function _setRandomCronjobTime()
    {
        $currentAccountCheckTime = trim(Mage::getStoreConfig('semknoxps/productupload/accountCheckCron'));
        if(strlen($currentAccountCheckTime) == 0 || in_array($currentAccountCheckTime, array('* * * * *','0 4 * * *')))
        {
            $min = rand(0,5) * 10;

            $possibleHours = array(4,5,6);
            $hour = $possibleHours[rand(0,count($possibleHours) - 1)];

            $value = $min.' '.$hour.' * * *'; //'30 * * * *';
            Mage::getModel('core/config')->saveConfig('semknoxps/productupload/accountCheckCron', $value);
        }

        if($this->_cronDisabled) return;

        $currentInitialUploadTime = trim(Mage::getStoreConfig('semknoxps/productupload/initialUploadCron'));
        if(strlen($currentInitialUploadTime) == 0 || $currentInitialUploadTime == '* * * * *')
        {
            $min = rand(0,5) * 10;

            $possibleHours = array(23,0,1,2,3,4,5);
            $hour = $possibleHours[rand(0,count($possibleHours) - 1)];

            $value = $min.' '.$hour.' * * *'; //'30 * * * *';
            Mage::getModel('core/config')->saveConfig('semknoxps/productupload/initialUploadCron', $value);
        }

    }

    /**
     * Start the inital upload for all stores. This functions writes a file to the log folder, that signalises
     * that the upload can start.
     */
    public function startInitialUpload()
    {
        if($this->_cronDisabled) return;

        // clean old upload logs
        //$this->_removeOldUploadLogFiles();

        /**
         * @var Semknox_ProductSearch_Model_AuthenticationManager $authManager
         */
        $authManager  = Mage::getModel('semknoxps/authenticationManager');

        $forStores = array();

        foreach($this->_storesCredentials as $storeId => $data)
        {
            $dir = 'upload-store-' . $storeId . '-cron';

            $customerId = $data['customerId'];
            $apiKey =  $data['apiKey'];

            $authStatus = $authManager->checkCredentials($customerId, $apiKey);

            if(!$authStatus) continue;

            // just start, if no upload info was found
            if(@$this->_fileHandler->fileExists($this->_logDir . $dir . DS . 'upload-info.json', false))
            {
                continue;
            }

            $initialUpload = Mage::getModel('semknoxps/initialUpload', array($storeId, 'cron', false));
            $initialUpload->start();

            $forStores[] = $storeId;

        }

        if(!count($forStores)){
            return 'no upload to start';
        } else {
            return 'started upload for storeIds: '.implode(', ', $forStores);
        }

    }


    /**
     * Continue initial uploads
     */
    public function continueInitialUpload()
    {
        if($this->_cronDisabled) return;

        $forStores = array();

        foreach($this->_storesCredentials as $storeId => $data)
        {
            $dir = 'upload-store-' . $storeId . '-cron';

            // if folder not exists, no cron upload running at the moment
            if(! @$this->_fileHandler->fileExists($this->_logDir . $dir . DS , false))
            {
                continue;
            }

            // do nothing if no upload info found
            if(! @$this->_fileHandler->fileExists($this->_logDir . $dir . DS . 'upload-info.json', false))
            {
                continue;
            }

            // go on with upload
            $initialUpload = Mage::getModel('semknoxps/initialUpload', array($storeId, 'cron'));
            $initialUpload->start();

            $forStores[] = $storeId;

        }

        if(!count($forStores)){
            return 'no upload to continue';
        } else {
            return 'continued upload for storeIds: '.implode(', ',$forStores);
        }

    }


    protected function _removeOldUploadLogFiles()
    {
        //--------------------------------------------------------------
        // delete completed data files after 5 days
        //--------------------------------------------------------------
        $files = $this->_getJsonFilesStartingWith('completed-upload-data-', false);

        foreach ($files as $file) {
            $filepath = $this->_logDir . $file['text'];

            // if file is 5 days (432.000 seconds) old then delete it
            if ($this->_fileHandler->fileExists($filepath) and (strtotime($file['mod_date']) < (time() - 432000))) {
                $this->_fileHandler->rm($filepath);
            }
        }


        //--------------------------------------------------------------
        // delete old (probably aborted) running files after 5 hours
        //--------------------------------------------------------------
        $files = $this->_getJsonFilesStartingWith('running-upload-', false);

        foreach ($files as $file) {
            $filepath = $this->_logDir . $file['text'];

            // if file is 5 hours (18.000 seconds) old then delete it
            if ($this->_fileHandler->fileExists($filepath) and (strtotime($file['mod_date']) < (time() - 18000))) {
                $this->_fileHandler->rm($filepath);
            }
        }
    }


    public function semknoxProductsUpdate()
    {
        // block while fullupdate is running (any store Id)
        foreach($this->_storesCredentials as $storeId => $data) {

            $initialUpload = Mage::getModel('semknoxps/InitialUpload', array($storeId, 'cron'));

            if($initialUpload->check() > 0) // percentage 0 > running
            {
                return;
            }
        }

        $this->_productsToUpdate();

        $this->_productsToDelete();

    }

    protected function _getProductIds($folder = '')
    {

        if(!$folder || !isset($this->_helper->_changedProductsFolder[$folder])) return array();

        $dir = Mage::getBaseDir('var') . DS . $this->_helper->_changedProductsFolder[$folder] . DS;

        //check if folder exists
        if(!@$this->_fileHandler->fileExists($dir, false)){
            return array();
        }

        //get all files in folder
        $this->_fileHandler->open(array(
            'path' => $dir
        ));
        $files = $this->_fileHandler->ls();
        $productIds = array();


        // get queue size form helper.... configurable
        $counter = $this->_updatesToSemknoxAtOnce;

        foreach($files as $file)
        {
            if((int) $file['text'] && $counter > 0)
            {
                $productIds[(int) $file['text']] = $file['text'];
                $counter--;
            }
        }

        return $productIds;

    }

    protected function _productsToDelete()
    {
        $folder = 'delete';
        $productIds = $this->_getProductIds($folder);

        if(empty($productIds)) return;

        $returnStatus = true;

        $storesCredentials = $this->_helper->getAllStoresCredentials();


        $returnStatus = true;
        foreach($storesCredentials as $storeId => $storeData) {

            $productManager = Mage::getModel('semknoxps/productManager', $storeId);

            foreach ($productIds as $productId => $file) {

                $result = json_decode($productManager->deleteProductById($productId), true);

                $status = false;
                if(isset($result['status']) && $result['status'] == 'success'){
                    $status = true;
                }
                $returnStatus = $status && $returnStatus;

                $this->_helper->removeProductFromUpdateQueue($productId);
            }
            break;

        }

        $log = implode(', ',array_keys($productIds));
        $status = ( $returnStatus) ? 'success' : 'failed';

        $logLevel = ($status == 'success') ? 6 : 3;

        $log = '[delete-queue] send product array('.$log.'): '.$status;
        $this->_helper->log($log, $logLevel, false, $storeId);

    }


    protected function _productsToUpdate()
    {
        $folder = 'update';
        $productIds = $this->_getProductIds('update');

        if(empty($productIds)) return;

        $storesCredentials = $this->_helper->getAllStoresCredentials();

        foreach($storesCredentials as $storeId => $storeData)
        {
            $productsArray = array();

            $productManager = Mage::getModel('semknoxps/productManager', $storeId);

            $products = Mage::getResourceModel('catalog/product_collection')
                            ->setStoreId($storeId)
                            ->addAttributeToSelect('*')
                            ->addAttributeToFilter(
                                'entity_id', array('in' => array_keys($productIds))
                            )
                            ->load();

            foreach($products as $p)
            {
                $productsArray[$p->getId()] = $p;
                $this->_helper->removeProductFromUpdateQueue($p->getId());

            }

            if(!empty($productsArray))
            {
                $log = implode(', ',array_keys($productsArray));

                $status = ($productManager->updateProducts($productsArray, false)) ? 'success' : 'failed';

                $logLevel = ($status == 'success') ? 6 : 3;

                $status .= ' - '.$productManager->getStatusMessage();

                $log = '[update-queue] send product array('.$log.'): '.$status;
                $this->_helper->log($log, $logLevel, false, $storeId);
            }

        }

    }

    // recheck for account persistence, needed to keep account alive
    public function accountCheck()
    {

        $storesCredentials = $this->_helper->getAllStoresCredentials();

        foreach($storesCredentials as $storeId => $storeData){

            $accountManager = Mage::getSingleton('semknoxps/accountManager');
            $data = $accountManager->getAccountStatistic($storeId);

            $queriesLastDay = 0;

            if(isset($data['queriesByTime'])){
                $queriesLastDay = array_sum($data['queriesByTime']);
            }

            $date = new DateTime();
            $date->modify('-1 day');
            $yesterday = $date->format('Y-m-d');

            try {

                $api = new Semknox_ProductSearch_Model_Api_APICommunicator($storeId);

                $api->setParam('qty', $queriesLastDay);
                $api->setParam('yesterday', $yesterday);

                $currentVersion = (string) Mage::getConfig()->getNode()->modules->Semknox_ProductSearch->version;
                $api->setParam('version', $currentVersion);

                $api->request('POST', 'https://sxlicense.goes.digital/:customerId');

            } catch(Zend_Http_Client_Exception $e)
            {
                $this->_helper->log('ERROR: accountCheck '. get_class());
            }

        }

    }

}