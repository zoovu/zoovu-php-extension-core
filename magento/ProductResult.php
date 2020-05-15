<?php

/* Copyright (C) Youbility Software - All Rights Reserved
* Unauthorized copying of this file, via any medium is strictly prohibited
* Proprietary and confidential
*/

/**
 * Class Semknox_ProductSearch_Model_ProductResult.
 *
 * After querying for products using the ProductManager class we get a set of results.
 * The purpose of this class is to provide convenient methods to work with the result.
 *
 *  # Get information about type of result
 *  - were products returned:      isProductResult()
 *  - were products returned:      isProductResult()
 *
 *  # Get information about products
 *  - get all product ids:     getProductIds()
 *  - get all products:        getProducts()
 *  - get number of products   getNumberOfProducts()
 *
 *  # Get other information
 *  - get the interpreted query:   getInterpretedQuery
 */
class Semknox_ProductSearch_Model_ProductResult
{
	//--------------------------------------------------------------
	// Private methods + constructor
	//--------------------------------------------------------------

	protected $_apiResult;

	public function __construct($apiResult)
	{
		$this->_apiResult = $apiResult;
	}

	/**
	 * Get the value $key from the api result. If it doesn't exist return $default.
	 * $key can be separated by dots get values at deeper levels.
	 *
	 * @param $key
	 * @param null $default
	 *
	 * @return null
	 */
	protected function _get($key, $default=null)
	{
		$keys  = explode('.', $key);
		$value = $this->_apiResult;

		foreach ($keys as $key) {
			if(isset($value[$key])) {
				$value = $value[$key];
			}
			else {
				return $default;
			}
		}

		return $value;
	}


	//--------------------------------------------------------------
	// Get information about type of result
	//--------------------------------------------------------------

	/**
	 * Returns true if type of result is actually a product listing.
	 * If it's for example a redirect we return false
	 */
	public function isProductResult()
	{
		return array_key_exists('searchResults', $this->_apiResult);
	}

	/**
	 * Returns true if the result is a redirect.
	 */
	public function isRedirectResult()
	{
		return array_key_exists('redirect', $this->_apiResult);
	}

	//--------------------------------------------------------------
	// Get information about redirects
	//--------------------------------------------------------------

	/**
	 * Returns the redirect url or false, if the result is not a redirect.
	 *
	 * @return string|false
	 */
	public function getRedirectUrl()
	{
		if(isset($this->_apiResult['redirect'])) {
			return $this->_apiResult['redirect'];
		}

		return false;
	}

	//--------------------------------------------------------------
	// Get information about products
	//--------------------------------------------------------------

	/**
	 * Return search results as array.
	 * @return array
	 */
	public function getProducts()
	{
		$result = array();
		#return $this->get('searchResults.0', array());
		$products = $this->_get('searchResults', array());

		foreach ($products as $productArray) {

			$relevantProducts = array();

			foreach($productArray as $data)
			{
				foreach($data['passOn'] as $passOn)
				{
					// check if passOn visibility exists
					if($passOn['key'] != 'visibility') continue;

					// if it exists, check if value greater 0
					if($passOn['value'] < 1){
						$data['articleNumber'] = $data['groupId'];
					} 

					// if value greater 0, it the product as relevant
					$relevantProducts[] = $data;

				}
			}

			if(empty($relevantProducts))
			{
				$relevantProducts[] = $productArray[0];
			}

			$productArray = $relevantProducts;

			$result = array_merge($result,$productArray);
		}

		return $result;
	}

	/**
	 * Return only an
	 * @return array
	 */
	public function getMagentoProductIds()
	{
		$products = $this->getProducts();
		$ids = array();

		// loop through all products we received
		foreach ($products as $product) {
			$ids[] = $product['articleNumber'];
		}

		return $ids;
	}

	/**
	 * Get the amount of products found.
	 * @return int
	 */
	public function getNumberOfProducts()
	{
		return $this->_get('groupedResultsAvailable', 0);
	}

	/**
	 * Return the sentence explaining the interpreted query. Returns false if the explanation is not set.
	 *
	 * @return string
	 */
	public function getQueryExplanation()
	{
		return $this->_get('interpretedQuery.explanation', false);
	}


	/**
	 * Get list of available orders.
	 *
	 * @return array
	 */
	public function getOrders()
	{
		$order = $this->_get('order', array());
		$orderSet = $this->_get('orderSet', null);

		if(! $orderSet) {
			return $this->_returnOrders($order, false);
		}

		$orderSetId = key($orderSet);
		$orderSetDirection = reset($orderSet);

		foreach ((array) $order as $key => $data) {
			if($data['id'] == $orderSetId) {
				$order[$key]['set'] = $orderSetDirection;
				break;
			}
		}

		return $this->_returnOrders($order);
	}

	/**
	 * Changes key 0 for "name"
	 * @param $orders
	 */
	protected function _returnOrders($orders, $orderSet = true)
	{
		foreach ($orders as $key => $data) {
			if($data['id'] === 0) {
				$orders[$key]['id'] = 'name';
			}
		}

		// add relevance/reset order
		if(count($orders))
		{
			$relevanceOrder = array(
				'id' => 'none',
				'relation' => 'none',
				'viewName' => 'Relevanz'
			);

			if(!$orderSet)
			{
				$relevanceOrder['set'] = 'ASC';
			}

			$orders = array_merge(array($relevanceOrder), $orders); // to put relevance in front
		}

		return $orders;
	}

	/**
	 * Return all available filters and the filters that are set.
	 *
	 * @return array
	 */
	public function getFilters()
	{
		$filters = $this->_get('filters', array());

		// get only filters that are available
		$filters = $this->_getOnlyAvailableFilters($filters);

		// sort filters by position
		usort($filters, array($this, 'sortFiltersByPosition'));

		// set filter id as key for filters
		$filters = $this->_makeFilterArrayAssociative($filters);

		// add set filters to array
		$filtersSet = $this->_get('filtersSet', array());
		$filters = $this->_addSetStatusToFilters($filters, $filtersSet);

		return $filters;
	}


	/**
	 * Return all available ContentSearchResults.
	 *
	 * @return array
	 */
	public function getContentSearchResults()
	{
		return $this->_get('contentSearchResults', array());
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
	protected function _getOnlyAvailableFilters( $filters )
	{
		foreach ($filters as $key => $filter) {
			if(isset($filter['available']) and !$filter['available']) {
				unset($filters[$key]);
			}
		}

		return $filters;
	}

	/**
	 * Compare the attribute "position" and sort the array ascending.
	 *
	 * @param $filterA
	 * @param $filterB
	 *
	 * @return int
	 */
	protected function sortFiltersByPosition($filterA, $filterB)
	{
		$a = $filterA['position'];
		$b = $filterB['position'];

		if($a == $b)  {
			return 0;
		}

		return ($a < $b) ? -1 : 1;
	}

	/**
	 * Adds an attribute "set" to each filter
	 *
	 * @param $filterArray An array of filters
	 * @param $filtersSet  An array of filters that are set
	 * @return array
	 */
	protected function _addSetStatusToFilters($filterArray, $filtersSet)
	{
		foreach ($filtersSet as $filterId => $setFilterData)
		{
			if(array_key_exists($filterId, $filterArray)) {
				$filterArray[$filterId]['set'] = $setFilterData;
			}

			// MULTI_SELECT
			if(isset($setFilterData['optionsSet'])) {
				$options = $setFilterData['optionsSet'];

				foreach ($options as $optionId) {
					if(isset($filterArray[$filterId]['options'][$optionId])) {
						$filterArray[$filterId]['options'][$optionId]['set'] = true;
					}

				}
			}
			// do nothing for RANGE
		}

		return $filterArray;
	}

	/**
	 * Makes filter and options array associative
	 *
	 * @param $filters
	 * @return array
	 */
	protected function _makeFilterArrayAssociative($filters)
	{
		$result = array();

		foreach ($filters as $filter) {
			// make options array associative
			if(isset($filter['options'])) {
				$optionResult = array();
				foreach ($filter['options'] as $option) {
					$optionResult[$option['id']] = $option;
				}

				// replace original options array with associative optionResult
				$filter['options'] = $optionResult;
			}

			// use filterId as key for array
			$result[$filter['id']] = $filter;
		}

		return $result;
	}
}