<?php

/* Copyright (C) Youbility Software - All Rights Reserved
* Unauthorized copying of this file, via any medium is strictly prohibited
* Proprietary and confidential
*/

/**
 * Class Semknox_ProductSearch_Model_AuthenticationManager
 *
 * This class is used for authentication purposes such as
 *  - check if given credentials are valid: checkCredentials()
 *  - request a demo key:                   requestDemoKey()
 */
class Semknox_ProductSearch_Model_AuthenticationManager {
	/**
	 * The current master key to request a demo account
	 */
	const MASTERKEY = 'w6tu34th413mh505mm14u6s2v2o11wiv';

	/**
	 * A description of the error that occurred
	 * @var string
	 */
	protected $_errorMsg = '';

	/**
	 * Return a new api instance
	 *
	 * @return Semknox_ProductSearch_Model_Api_APICommunicator
	 */
	protected function _newApi()
	{
		return new Semknox_ProductSearch_Model_Api_APICommunicator();
	}

	/**
	 * Check if the given credentials are valid.
	 *
	 * @param $customerId
	 * @param $apiKey
	 * @return bool
	 */
	public function checkCredentials($customerId, $apiKey)
	{
		$api = $this->_newApi();

		$api->setAuthentication($customerId, $apiKey);


		try {
			$response = $api->request('GET', 'customers/:customerId/status');

			// return true if status is 200
			$status = ($response->getStatus() == 200);

			if(!$status) {
				$response = json_decode($response->getBody(), true);
				$this->_errorMsg = $response['message'];
			}

			return $status;
		}
		catch(Zend_Http_Client_Exception $e)
		{
			$this->_errorMsg = $e->getMessage();

			return false;
		}
	}

	/**
	 * Request a demo key from Semknox. Demo keys are valid limited time only
	 *
	 * @param $shopUrl  This shops url
	 * @return array|false Returns an array with demo key information or false if it failed. To get the error message on failure
	 * use getErrorMessage() afterwards.
	 * {
			"testKey": "mqwsx0ju9tvj817q51khm5363dy71e9f",
			"customerName": "COM12314654",
			"customerId": 117,
			"status": "TEST_KEY_SENT",
			"validThrough": 1458570846
		}
	 */
	public function requestDemoKey($shopUrl, $attributes=array())
	{
		$api = $this->_newApi();

		$api->setParam('masterKey', self::MASTERKEY);

		$api->setParam('shopUrl', $shopUrl);

		// add shopId and language to query parameters
		if(array_key_exists('shopId', $attributes)) {
			$api->setParam('shopId', $attributes['shopId']);
			unset($attributes['shopId']);
		}
		if(array_key_exists('language', $attributes)) {
			$api->setParam('language', $attributes['language']);
			unset($attributes['language']);
		}

		// ad moreInformation to query parameters
		$moreInformation = array();
		foreach ($attributes as $key => $value) {
			$moreInformation[$key] = $value;
		}
		if($moreInformation) {
			$api->setParam('moreInformation', urlencode(json_encode($moreInformation)));
		}

		// request key
		try {
			$response = $api->request('GET', 'customers/testkey');

			$response = json_decode($response->getBody(), true);

			if(isset($response['code']) and $response['code'] != 200) {
				$this->_errorMsg = $response['message'];

				return false;
			}

			return $response;
		}
		catch(Zend_Http_Client_Exception $e) {
			$this->_errorMsg = $e->getMessage();

			return false;
		}
	}

	/**
	 * Get a descriptive error message
	 * @return string
	 */
	public function getErrorMessage()
	{
		return $this->_errorMsg;
	}
}