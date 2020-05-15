<?php

/* Copyright (C) Youbility Software - All Rights Reserved
* Unauthorized copying of this file, via any medium is strictly prohibited
* Proprietary and confidential
*/

/**
 * Class Semknox_ProductSearch_Model_ProductManager
 *
 * This class is used to handle all product related actions.
 *
 *  # Query Suggestions
 *  - get suggestions by a given query:     getSuggestions()
 *
 *  # Product Retrieval
 *  - get products by a given query:        getProducts()
 *  - get products by magento id:           getProductsById()
 *
 *  # Product Submission
 *  - send product information to SEMKNOX Api:  updateProducts()
 *
 *  # Setting request information
 *  - set the amount of results we want to receive:     setLimit()
 *  - set the page / offset:                            setPage()
 */
class Semknox_ProductSearch_Model_ProductManager
{
	/**
	 * No products were uploaded to Semknox, usally because they were said to be not visible in the search.
	 */
	const STATUS_UPLOAD_NO_PRODUCTS = 0;

	/**
	 * Products were successfully created
	 */
	const STATUS_UPLOAD_SUCCESS = 1;

	/**
	 * There was an error creating the products
	 */
	const STATUS_UPLOAD_FAIL = 2;

	/**
	 * If this class runs in testmode
	 * @var bool
	 */
	public $TESTMODE = false;


	/**
	 * The page of products to be received
	 * @var int
	 */
	protected $_page = 1;

	/**
	 * The amount of products per page to be received
	 * @var int
	 */
	protected $_limit = 0;//20;

	/**
	 * The filter to apply to the search
	 * array(
	     array("id" => 2444, "values" => array("9478", "8484")),

	 * )
	 * @var array
	 */
	protected $_filter = array();

	/**
	 * The order for the current search
	 *  array("id" => 2648, "direction" => "ASC")
	 * @var array
	 */
	protected $_order = array();


	/**
	 * The current requests status.
	 * array(
	 *    'message' => 'Something somewhere went terribly wrong'
	 * )
	 *
	 * @var array
	 */
	protected $_status;

	protected $_currentStoreId;
	protected $_productHelper;
	protected $_helper;


	//--------------------------------------------------------------
	// Private methods + constructor
	//--------------------------------------------------------------


	public function __construct($storeId = null)
	{
		$this->_currentStoreId = $storeId;
		$this->_productHelper = Mage::helper('catalog/product');
		$this->_helper = Mage::helper('semknoxps');
	}

	public function setCurrentStoreId($storeId)
	{
		$this->_currentStoreId = $storeId;
	}

	/**
	 * Create a new instance of the API Communicator class.
	 *
	 * @return Semknox_ProductSearch_Model_Api_APICommunicator
	 */
	protected function _newApi()
	{
		$api = new Semknox_ProductSearch_Model_Api_APICommunicator($this->_currentStoreId);

		return $api;
	}

	protected function _getResponseBody(Zend_Http_Response $response)
	{
		$body = $response->getBody();

		return $body;
	}

	//--------------------------------------------------------------
	// Query Suggestions
	//--------------------------------------------------------------

	/**
	 * Get product and query suggestions for the curent query
	 *
	 * @param $query The search query.
	 * @param $limit Maximum amount of product suggestions
	 * @param $numSuggests Maximum amount of query suggestions
	 *
	 * @return array
	 */
	public function getSuggestions($query=null, $limit = null, $numSuggests = null)
	{
		$api = $this->_prepareSuggestionRequest($query, $limit, $numSuggests);

		$response = $api->request('GET', 'queries/suggest');

		return $this->_getResponseBody( $response );
	}

	/**
	 * Get the url from where to retrieve search results. Needed for Client-Side search (ajax requests).
	 *
	 * @param null $query
	 * @param null $limit
	 *
	 * @return string
	 */
	public function getSearchUrl($query=null, $limit = null)
	{
		$api = $this->_prepareSearchRequest($query, $limit);

		return $api->getRequestUri('products/search');
	}

	protected function _prepareSearchRequest($query, $limit)
	{
		$api = $this->_newApi();

		if($query) {
			$api->setQuery($query);
		}

		if($limit) {
			$api->setLimit($limit);
		}

		$userGroup = $this->_helper->getCurrentUsersGroup();
		$api->setUserGroup($userGroup);

		return $api;
	}

	/**
	 * Get the url from where to retrieve suggestions. Needed for Unibox.
	 *
	 * @param null $query
	 * @param null $limit
	 * @param null $numSuggests
	 *
	 * @return string
	 */
	public function getSuggestUrl($query=null, $limit = null, $numSuggests = null)
	{
		$api = $this->_prepareSuggestionRequest($query, $limit, $numSuggests);

		return $api->getRequestUri('queries/semanticSuggest');
	}

	protected function _prepareSuggestionRequest($query, $limit, $numSuggests)
	{
		$api = $this->_newApi();

		if($query) {
			$api->setQuery($query);
		}

		if($limit) {
			$api->setLimit($limit);
		}

		if($sessionId = Mage::getSingleton("core/session")->getEncryptedSessionId()) {
			$api->setParam('sessionId', $sessionId);
		}

		if($numSuggests) {
			$api->setParam('numSuggests', $numSuggests);
		}

		$userGroup = $this->_helper->getCurrentUsersGroup();
		$api->setUserGroup($userGroup);

		return $api;
	}


	public function sendQueryLog($queries)
	{
		$api = $this->_newApi();

		if(is_array($queries)) {
			$queries = urlencode(json_encode($queries));
		}

		$api->setParam('queryLogJson', $queries);

		try {
			$response = $api->request('POST', 'queries/addLog/customers/:customerId' );

			return json_decode( $response->getBody(), true );
		}
		catch(Zend_Http_Client_Exception $e) {

			$this->_helper->log('API TIMEOUT: sendQueryLog ' . get_class());
		}
	}



	public function sendAutosuggestMappings($ccss)
	{
		$api = $this->_newApi();

		if(is_array($ccss)) {
			$ccss = urlencode(json_encode($ccss));
		}

		$api->setParam('ccssJson', $ccss);

		try {
			$response = $api->request('POST', 'queries/updateCcss' );

			return json_decode( $response->getBody(), true );
		}
		catch(Zend_Http_Client_Exception $e) {

			$this->_helper->log('API TIMEOUT: sendAutosuggestMappings ' . get_class());
		}

	}

	//--------------------------------------------------------------
	// Product Retrieval
	//--------------------------------------------------------------

	/**
	 * Retrieve products from the API by a given query.
	 * Each element from the returned array is *NOT* of class `Mage_Catalog_Model_Product` but an array itself.
	 *
	 * @param $query The search query
	 *
	 * @return Semknox_ProductSearch_Model_ProductResult
	 */
	public function getProducts($query)
	{
		$api = $this->_newApi();

		$api->setLimit($this->_limit);

		$api->setOffset(($this->_page - 1) * $this->_limit);

		$api->setQuery($query);

		$userGroup = $this->_helper->getCurrentUsersGroup();
		$api->setUserGroup($userGroup);

		if($this->_filter) {
			$api->setParam('filters', $this->_filter);
		}
		if($this->_order) {
			$api->setParam('order', $this->_order);
		}

		if($sessionId = Mage::getSingleton("core/session")->getEncryptedSessionId()) {
			$api->setParam('sessionId', $sessionId);
		}

		$logRequest = false; // for debugging
		$response = $api->request('POST', 'products/search', $logRequest);

		$response = json_decode($response->getBody(), true);

		// set Tracking cookie
		if(isset($response['queryId'])){
			setcookie('sxLatestQueryId',$response['queryId'],strtotime("+1 year"),'/');
		}

		return $this->_getModel('semknoxps/ProductResult', $response);
	}

	protected function _getModel($model, $data)
	{
		if($this->TESTMODE) {
			return Mage::getModel($model, $data);
		}
		else {
			return Mage::getSingleton($model, $data);
		}
	}

	//--------------------------------------------------------------
	// Product Submission
	//--------------------------------------------------------------

	/**
	 * Transform Magento products to semknox api array.
	 * @param $products
	 */
	public function transformProducts($products)
	{
		$attributes = array();

		$this->_productHelper->setCurrentStoreId = $this->_currentStoreId;

		// initialise the transformer to convert magento product to semknox api array
		$transform = Mage::helper('semknoxps/productTransformer');

		$transform->setCurrentStoreId($this->_currentStoreId);

		// reinit
		$transform->init();

		// transform products and add them to $attributes
		foreach ($products as $product) {
			$data = $transform->toApi($product);

			if($data) {
				$attributes[] = $data;
			}

		}

		return $attributes;
	}

	/**
	 * Takes an array of products to update. Each product is of type
	 *   `Mage_Catalog_Model_Product`.
	 * If all products are updated correctly this method returns true.
	 *
	 * @param array|Mage_Catalog_Model_Resource_Product_Collection $products
	 * @param $full  Is the upload a full upload? (products that were not uploaded are getting deleted)
	 *
	 * @return bool
	 */
	public function updateProducts($products, $full=false)
	{
		$returnStatus = true;
		$productsToDelete = array();

		foreach($products as $key => $product)
		{
			// if products are disabled, they should be deleted on SEMKNOX
			if(!$this->_productHelper->canShow($product))
			{
				unset($products[$key]);
				$productsToDelete[] = $product->getId();
			}
		}

		if(!empty($productsToDelete))
		{
			//todo: improve this .... the whole updateProducts, sendProductUpdate, sendProductDelete
			$this->sendProductDelete($productsToDelete);
			$this->_helper->log('[delete-queue] send product array('.implode(',', $productsToDelete).')', 0,false, $this->_currentStoreId);
		}

		$productData = $this->transformProducts($products);

		if(empty($productsToDelete) && empty($productData)) {
			return $this->_returnStatus(self::STATUS_UPLOAD_NO_PRODUCTS);
		}

		return $this->sendProductUpdate($productData, $full);
	}

	/**
	 * Send an api request to semknox with data to update products
	 *
	 * @param array $productData
	 * @param bool $full Do a full update?
	 *
	 * @return bool
	 */
	public function sendProductUpdate($productData, $full=false)
	{
		$api = $this->_newApi();

		// api endpoint for upload
		$endpoint = 'products';

		$body = urlencode((json_encode($productData)));

		if($full === true) {
			$endpoint = 'products/batchInput';
		}

		$api->setParam('productsJsonArray', $body);

		try {
			$response = $api->request('PUT', $endpoint);

			$responseBody = json_decode($response->getBody(), true);

			$status = ($response->getStatus() == 200 and $responseBody['status'] == 'success');

			if($status) {
				return $this->_returnStatus(self::STATUS_UPLOAD_SUCCESS, $responseBody);
			}
			else {
				return $this->_returnStatus(self::STATUS_UPLOAD_FAIL, $responseBody);
			}
		}
		catch(Zend_Http_Client_Exception $e) {

			$this->_helper->log('API TIMEOUT: sendProductUpdate ' . get_class());
		}

	}


	//--------------------------------------------------------------
	// Product Deletion
	//--------------------------------------------------------------

	/**
	 * Delete multiple products by IDs from the Semknox api.
	 *
	 * @param Array $productIds
	 * @return string
	 */
	public function sendProductDelete($productIds)
	{
		$api = $this->_newApi();

		$api->setParam('articleNumbers', $productIds);

		$endpoint = 'products/deletePost';

		try {
			$response = $api->request('POST', $endpoint);

			$responseBody = json_decode($response->getBody(), true);

			$status = ($response->getStatus() == 200 and $responseBody['status'] == 'success');

			if($status) {
				return $this->_returnStatus(self::STATUS_UPLOAD_SUCCESS, $responseBody);
			}
			else {
				return $this->_returnStatus(self::STATUS_UPLOAD_FAIL, $responseBody);
			}
		}
		catch(Zend_Http_Client_Exception $e) {

			$this->_helper->log('API TIMEOUT: deleteProductUpdate ' . get_class());
		}
	}


	/**
	 * Delete a single product from the Semknox api.
	 *
	 * @param Mage_Catalog_Model_Product $product The product to be deleted
	 * @return string
	 */
	public function deleteProduct(Mage_Catalog_Model_Product $product)
	{
		return $this->deleteProductById($product->getId());
	}


	/**
	 * Delete a single product from the Semknox Api by
	 * its Magento id.
	 *
	 * @param int $productId
	 */
	public function deleteProductById($productId)
	{
		$api = $this->_newApi();

		$api->setParam('articleNumber', $productId);

		$response = $api->request('DELETE', 'products');

		return $this->_getResponseBody($response);
	}

	/**
	 * Delete ALL products from the Semknox api.
	 *
	 * @return mixed
	 */
	public function deleteAllProducts()
	{
		return $this->deleteProductById('all');
	}

	/**
	 * Clear input cache before doing an initial upload.
	 *
	 * @return mixed
	 */
	public function clearInputCache()
	{
		$api = $this->_newApi();
		
		$response = $api->request('GET', 'products/clearInput');

		$this->_helper->log('Sent products/clearInput', null, false,  $this->_currentStoreId);

		return $this->_getResponseBody($response);
	}

	/**
	 * Notify SEMKNOX that a full update has been sent
	 *
	 * @return mixed
	 */
	public function fullUpdateSent()
	{
		$api = $this->_newApi();

		$response = $api->request('GET', 'products/fullUpdate');

		$this->_helper->log('Sent products/fullUpdate', null, false, $this->_currentStoreId);

		return $this->_getResponseBody($response);
	}

	//--------------------------------------------------------------
	// Setting request information
	//--------------------------------------------------------------


	/**
	 * Set the current page.
	 *
	 * @param $_page
	 * @return $this
	 */
	public function setPage($_page)
	{
		$this->_page = $_page;

		return $this;
	}

	/**
	 * Set the amount of products to be received.
	 *
	 * @param $_limit
	 * @return $this
	 */
	public function setLimit($_limit)
	{
		$this->_limit = $_limit;

		return $this;
	}

	/**
	 * get the amount of products to be received.
	 *
	 * @param $_limit
	 * @return $this
	 */
	public function getLimit()
	{
		return $this->_limit;
	}


	/**
	 * Set available filters. Filters can be passed in as follows:
	 *
	 * MULTI_SELECT filter:
	 *  $filter = [$filterId1 => [$optionId1, $optionId2, $optionId3], $filterId2 => [...]]
	 *
	 * RANGE filter:
	 *  $filter = [$filterId1 => ['min'=>$min,'max'=>$max], $filterId2 => [...]]
	 *
	 * @param array $_filter
	 *
	 * @return $this
	 */
	public function setFilter($_filter = array())
	{
		$semknoxFilter = array();

		foreach ($_filter as $filterId => $values) {
			// MULTI_SELECT
			if(is_numeric ( key($values) )) {
				$semknoxFilter[] = array("id" => $filterId, "values" => $values);
			}

			// RANGE
			else if(isset($values['min']) or isset($values['max']))
			{
				$rangeFilter = array('id' => $filterId);

				if(isset($values['min'])) {
					$rangeFilter['minValue'] = $values['min'];
				}

				if(isset($values['max'])) {
					$rangeFilter['maxValue'] = $values['max'];
				}

				if(isset($values['unitName'])) {
					$rangeFilter['unitName'] = $values['unitName'];
				}

				$semknoxFilter[] = $rangeFilter;
			}
		}

		$this->_filter = $semknoxFilter;

		return $this;
	}


	/**
	 * Set the order for the current request. If $orderId is 0 or null and $direction is 'ASC' the default
	 * order will be taken.
	 *
	 * @param string $orderId
	 * @param string $direction
	 *
	 * @return $this
	 */
	public function setOrder($orderId, $direction='ASC')
	{
		$direction = strtoupper($direction);

		if(($orderId === null or $orderId === 'null') and $direction == 'ASC') {
			$this->_order = array();
		}
		else {
			if($orderId === 'name') {
				$orderId = "0";
			}

			$this->_order = array("id" => (string) $orderId, "direction" => $direction);
		}

		return $this;
	}

	//--------------------------------------------------------------
	// Status information
	//--------------------------------------------------------------

	protected function _returnStatus($statusCode, $response = array())
	{
		switch($statusCode)
		{
			case self::STATUS_UPLOAD_NO_PRODUCTS:
				$this->_status = array(
					'message' => 'No products could be sent to semknox'
				);
				return true;

			case self::STATUS_UPLOAD_SUCCESS:
				$this->_status = array(
					'message' => 'Products were successfully sent to semknox'
				);
				return true;

			case self::STATUS_UPLOAD_FAIL:
				$this->_status = array(
					'message' => (isset($response['error_message'])) ? $response['error_message'] : json_encode($response)
				);
				return false;

		}
	}

	/**
	 * Returns the error message as string.
	 *
	 * @return string
	 */
	public function getStatusMessage()
	{
		return $this->_status['message'];
	}
}