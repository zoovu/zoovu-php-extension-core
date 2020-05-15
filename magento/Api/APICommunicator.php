<?php
/* Copyright (C) Youbility Software - All Rights Reserved
* Unauthorized copying of this file, via any medium is strictly prohibited
* Proprietary and confidential
*/


/**
 * Class Semknox_ProductSearch_Model_Api_APICommunicator
 * Provides methods to communicate with the Semknox API.
 */
class Semknox_ProductSearch_Model_Api_APICommunicator
{
	const LOGINBASE = 'https://login.semknox.com/';

	const BASE = 'https://api-magento.semknox.com/';
	const BASE_DEV = 'https://stage-magento.semknox.com/';

	/**
	 * The maximum allowed value for _limit
	 */
	const MAXLIMIT = 108;


	protected $_apiBaseUrl;

	/**
	 * Request parameters (GET parameters)
	 * @var
	 */
	protected $_params;

	/**
	 * The client to be used
	 * @var Varien_Http_Client
	 */
	protected $_client;

	/**
	 * The content type to use
	 * @var string
	 */
	protected $_contentType = 'application/x-www-form-urlencoded';

	protected $_storeId;


	public function __construct($storeId=null)
	{
		$this->_storeId = $storeId;

		$this->_apiBaseUrl = $this->_getApiBaseUrl();

		$this->_client = new Zend_Http_Client();
		$this->_client->setConfig(array(
			'timeout' => 15
		));
	}

	/**
	 * Set the query for the current request
	 */
	public function setQuery($query)
	{
		$this->setParam('query', $query);
	}

	/**
	 * Set a parameter for the current request
	 *
	 * @param $name
	 * @param $val
	 *
	 * @return $this
	 */
	public function setParam($name, $val)
	{
		if(is_array($val)) {
			$val = json_encode($val);
		}

		$this->_params[$name] = $val;

		return $this;
	}

	/**
	 * Set the parameter "offset".
	 *
	 * @param $_offset
	 *
	 * @return Semknox_ProductSearch_Model_Api_APICommunicator
	 */
	public function setOffset($_offset)
	{
		return $this->setParam('offset', $_offset);
	}

	/**
	 * Set the parameter "limit".
	 *
	 * @param $_limit
	 *
	 * @return Semknox_ProductSearch_Model_Api_APICommunicator
	 */
	public function setLimit($_limit)
	{
		return $this->setParam('limit', $_limit);
	}

	/**
	 * Set the parameter "userGroup".
	 *
	 * @param $_userGroup
	 *
	 * @return Semknox_ProductSearch_Model_Api_APICommunicator
	 */
	public function setUserGroup($_userGroup)
	{
		return $this->setParam('userGroup', $_userGroup);
	}

	/**
	 * Send the request.
	 *
	 * @param $method
	 * @param $uri
	 *
	 * @return Zend_Http_Response
	 * @throws Zend_Http_Client_Exception
	 */
	public function request($method, $uri, $logRequest = false)
	{
		if( ! $this->_isAuthenticationSet()) {
			$this->_getApiCredentials();
		}

		// if limit is bigger than maxLimit, set limit to maxLimit
		if(!isset($this->_params['limit']) || !$this->_params['limit'] || $this->_params['limit'] > self::MAXLIMIT)
		{
			$this->setLimit(self::MAXLIMIT);
		}

		// replace :customerId by real customerId in url
		if(strpos($uri, ':customerId') !== false) {
			$uri = str_replace(':customerId', $this->_params['customerId'], $uri);
			unset($this->_params['customerId']);
		}

		// add apiBaseUrl if needed
		if(strpos($uri, '://') !== false) {
			$this->_params['uri'] = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB);
		} else {
			$uri = $this->_apiBaseUrl. $uri;
		}

		$this->_client
			->setUri($uri)
			->setMethod($method);

		if(in_array(strtolower($method), array('get', 'delete'))) {
			$this->_client->setParameterGet($this->_params);
		}
		else {
			$body = array();
			foreach ( $this->_params as $name => $value ) {
				if(is_array($value)) {
					$value = urlencode(json_encode($value));
				}

				$body[] = $name . '=' . $value;
			}

			$this->_client->setHeaders('Content-Type', $this->_contentType);
			$this->_client->setRawData(join('&', $body));
		}

		$request = $this->_client->request();

		if($logRequest)
		{
			/* @var $helper Semknox_ProductSearch_Helper_Data */
			$helper = Mage::helper('semknoxps');
			$helper->log($this->_client->getLastRequest());

		}

		return $request;
	}

	/**
	 * Return the uri with Login parameters for the users BackOffice Login.
	 * @param $uri
	 *
	 * @return string
	 */
	public function getBackOfficeLoginUri()
	{
		if( ! $this->_isAuthenticationSet()) {
			$this->_getApiCredentials();
		}

		$uri   = self::LOGINBASE;
		$query = http_build_query($this->_params);

		if($query) {
			$uri .= '?' . $query;
		}

		return $uri;
	}

	/**
	 * Return the uri with all parameters for the current request.
	 * @param $uri
	 * @param $addCredentials
	 *
	 * @return string
	 */
	public function getRequestUri($uri, $addCredentials = true)
	{

		if( $addCredentials && ! $this->_isAuthenticationSet()) {
			$this->_getApiCredentials();
		}

		$uri   = $this->_apiBaseUrl . $uri;
		$query = http_build_query($this->_params);

		if($query) {
			$uri .= '?' . $query;
		}

		return $uri;
	}

	/**
	 * Set user authentification
	 *
	 * @param $customerId
	 * @param $apiKey
	 *
	 * @return $this
	 */
	public function setAuthentication($customerId, $apiKey)
	{
		$this->setParam('customerId', $customerId);
		$this->setParam('apiKey', $apiKey);

		return $this;
	}

	/**
	 * Returns true if the authentication parameters are set, otherwise false.
	 *
	 * @return bool
	 */
	protected function _isAuthenticationSet()
	{
		return isset(
			$this->_params['customerId'],
			$this->_params['apiKey']
		);
	}

	/**
	 * Reads API credentials from configuration
	 */
	protected function _getApiCredentials()
	{
		if($this->_storeId)
		{
			$storeId = $this->_storeId;
		} else {
			/* @var $helper Semknox_ProductSearch_Helper_Data */
			$helper = Mage::helper('semknoxps');
			$storeId = $helper->getCurrentStoreId();
		}

		$customerId = Mage::getStoreConfig('semknoxps/login/customerId', $storeId);
		$apiKey     = Mage::getStoreConfig('semknoxps/login/apiKey', $storeId);

		return $this->setAuthentication($customerId, $apiKey);
	}

	/**
	 * Gets current Base URL (Sandbox or Live)
	 *
	 * * @return string
	 */
	protected function _getApiBaseUrl()
	{
		/* @var $helper Semknox_ProductSearch_Helper_Data */
		$helper = Mage::helper('semknoxps');

		if($this->_storeId)
		{
			$storeId = $this->_storeId;
		} else {
			$storeId = $helper->getCurrentStoreId();
		}

		if($helper->isSandboxMode($storeId))
		{
			return self::BASE_DEV;
		} else {
			return self::BASE;
		}

	}

}