<?php

/* Copyright (C) Youbility Software - All Rights Reserved
* Unauthorized copying of this file, via any medium is strictly prohibited
* Proprietary and confidential
*/

class Semknox_ProductSearch_Model_AccountManager
{

	/**
	 * @var Semknox_ProductSearch_Model_ProductManager
	 */
	protected $_productManager;

	/**
	 * @var bool
	 */
	protected $_availableFilters = false;

	/**
	 * @var bool
	 */
	protected $_availableFiltersSet = false;

	/**
	 * @var Semknox_ProductSearch_Helper_Data
	 */
	protected $_helper;

	public function __construct()
	{
		$this->_helper = Mage::helper('semknoxps');
	}


	protected function _newApi($storeId = null)
	{
		$api = new Semknox_ProductSearch_Model_Api_APICommunicator($storeId);

		return $api;
	}


	public function getAccountLoginUrl()
	{
		$api = $this->_newApi();

		return $api->getBackOfficeLoginUri();
	}


	public function getAccountStatistic($storeId = null)
	{
		try
		{
			$api = $this->_newApi($storeId);

			$response = $api->request( 'GET', 'statistics/dashboard' );

			return json_decode( $response->getBody(), true );
		}
		catch(Zend_Http_Client_Exception $e)
		{
			$this->_helper->log('API TIMEOUT: getAccountStatistic '. get_class());

			return array();
		}
	}


	public function getAccountStatus()
	{
		try
		{
			// todo: improve custormerId preCheck
			$storeId = $this->_helper->getCurrentStoreId();
			$customerId = Mage::getStoreConfig('semknoxps/login/customerId', $storeId);

			if(!$customerId)
			{
				return array(
					'status' => 'CREDENTIALS MISSING'
				);
			}

			$api = $this->_newApi();

			$response = $api->request('GET', 'customers/:customerId/status');
			$responseBody = json_decode($response->getBody(), true);

			return $responseBody;
		}
		catch(Zend_Http_Client_Exception $e)
		{
			if(isset($responseBody) && $responseBody)
			{
				return $responseBody;
			}

			$this->_helper->log('API TIMEOUT: getAccountStatus '. get_class());

			return array(
				'status' => 'TIMEOUT'
			);
		}
	}



	//--------------------------------------------------------------
	// Filter methods
	//--------------------------------------------------------------

	public function getSetFiltersUrl()
	{
		$api = $this->_newApi();

		return $api->getRequestUri('filters');
	}




	public function getAvailableFilters()
	{
		if($this->_availableFilters !== false) return $this->_availableFilters;

		$api = $this->_newApi();
		$response         = $api->request('GET', 'filters/templates');
		$availableFilters = json_decode($response->getBody(), true);
		$availableFilters = $this->_filterByAvailable( $availableFilters );

		$this->_availableFilters = $availableFilters;

		return $availableFilters;
	}

	/**
	 * Get only filters that have the attribute
	 *  "available" set to true
	 * or no attribute "available". Effectively removes all filters that have the property
	 * available set to false.
	 *
	 * @param $filters
	 *
	 * @return mixed
	 */
	protected function _filterByAvailable( $filters )
	{
		foreach ($filters as $key => $filter) {
			if(isset($filter['available']) and !$filter['available']) {
				unset($filters[$key]);
			}
		}

		return $filters;
	}

	public function getAvailableFiltersSet()
	{
		if($this->_availableFiltersSet !== false) return $this->_availableFiltersSet;

		$api = $this->_newApi();
		$response            = $api->request('GET', 'filters');
		$currentlySetFilters = json_decode($response->getBody(), true);

		$this->_availableFiltersSet = $currentlySetFilters;

		return $currentlySetFilters;
	}


	/**
	 * Returns an array of filters, grouped by "range" or "multi_select".
	 * Filters that are activated in the Semknox backoffice have the attribute "set":true.
	 */
	public function getAvailableFiltersGroupedByType()
	{

		$availableFilters = $this->getAvailableFilters();
		$currentlySetFilters = $this->getAvailableFiltersSet();

		$result = array();

		foreach ($availableFilters as $filter) {
			$set = false;

			foreach ($currentlySetFilters as $key => $setFilter) {
				if($filter['templateFilterId'] == $setFilter['templateFilterId']) {
					$set = true;
					unset($currentlySetFilters[$key]);
					break;
				}
			}

			$filter['set'] = $set;
			$result[$filter['type']][] = $filter;
		}

		return $result;
	}
}