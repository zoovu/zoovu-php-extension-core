<?php

namespace Semknox\Core\Services\Search;

use Semknox\Core\Services\Search\Filters\TreeFilter;
use Semknox\Core\Services\Search\Interfaces\SearchResponseInterface;
use Semknox\Core\Services\Search\Sorting\SortingOption;
use Semknox\Core\Services\Traits\ArrayGetTrait;

class SearchResponse implements SearchResponseInterface
{
    use ArrayGetTrait;

    /**
     * Raw Semknox search response
     * @var array
     */
    private $response;

    public function __construct(array $response)
    {
        $this->response = $response;
    }

    private function get($key, $default=null)
    {
        return $this->arrayGet($this->response, $key, $default);
    }

    /**
     * Return information
     * @return array
     */
    public function getInterpretedQuery()
    {
        return $this->get('interpretedQuery');
    }

    /**
     * Return the answer text generated by Semknox for this query.
     * @return string
     */
    public function getAnswerText()
    {
        return $this->get('answerText');
    }

    /**
     * Return all filter options available for this search.
     * @return TreeFilter[]
     */
    public function getAvailableFilters()
    {
        $activeFilters = $this->getActiveFilters();
        $filters = $this->get('filterOptions');
        $result = [];

        foreach($filters as $filter) {
            $result[] = SearchResultFactory::getFilter($filter, $activeFilters);
        }

        return $result;
    }

    /**
     * Return filters that are active for this query.
     * @return mixed|null
     */
    public function getActiveFilters()
    {
        return array_filter($this->get('activeFilterOptions'));
    }

    /**
     * Get available sorting options.
     *
     * @return SortingOption[]
     */
    public function getAvailableSortingOptions()
    {
        $activeSort = $this->getActiveSortingOption();
        $sortingOptions = $this->get('sortingOptions');
        $result = [];

        foreach($sortingOptions as $option) {
            $result[] = SearchResultFactory::getSortingOption($option, $activeSort);
        }

        return $result;
    }

    /**
     * Return the sorting options that are active for this query.
     * @return array
     */
    public function getActiveSortingOption()
    {
        return $this->get('activeSortingOption');
    }

    /**
     * Return the number of total results for this search. This includes CONTENT and PRODUCT results. To return only the number of results for a specific resultGroup, pass the $resultGroup parameter. For example `->getTotalResults('products')` will return the number of product results.
     *
     * @param null $resultGroup TotalResults of which resultGroup. If null it will return the number of results for all resultGroups combined.
     * @return int
     */
    public function getTotalResults($resultGroup=null)
    {
        return $resultGroup
            ? $this->getResultGroup($resultGroup, 'totalResults')
            : $this->get('totalResults');
    }

    /**
     * Get the total number of products found for this request
     * @return int
     */
    public function getTotalProductResults()
    {
        return $this->getTotalResults('products');
    }

    /**
     * Alias for `getResults('products')`.
     * @return Product[]
     */
    public function getProducts()
    {
        return $this->getResults('products');
    }

    /**
     * Return all products. Does not group variations together. Alias for `getResults('products', true)`.
     * @return Product[]
     */
    public function getProductsFlattened()
    {
        return $this->getResults('products', true);
    }

    /**
     * Return an array of ResultItems (i.e. the products)
     * @param bool $flattened Return grouped products as standalone products.
     * @return Product[]
     */
    public function getResults($groupType, $flattened=false)
    {
        $return = [];
        $results = $this->getResultGroup($groupType, 'results');

        $resultFactory = ($groupType == 'products') ? 'getProduct' : 'getContent';

        foreach($results as $items) {

            if($groupType == 'products'){
                
                if($flattened) {
                    foreach($items as $item) {
                        $return[] = SearchResultFactory::$resultFactory([$item]);
                    }
                }
                else {
                    $return[] = SearchResultFactory::$resultFactory($items);
                }

            } else {

                // custom type
                $return = array_merge($return, SearchResultFactory::$resultFactory($items));

            }
        }

        return $return;
    }

    /**
     * Return the searchResult-group with the given type. The type can be "products" or "content".
     * @param $groupType
     * @param $key Which key to return from the found searchResultGroup. Returns the whole resultGroup when no key is specified.
     * @return array
     */
    private function getResultGroup($groupType, $key=null)
    {
        $return = [];

        foreach($this->get('searchResults') as $searchResult) {

            if($searchResult['type'] === $groupType) {
                if(!$key) {

                    if($groupType == 'products'){
                        return $searchResult;
                    } 

                    $return[] = $searchResult;
                }
                else {

                    if ($groupType == 'products'){
                        return isset($searchResult[$key]) ? $searchResult[$key] : [];
                    }

                    $return[] = $searchResult;
                    
                }
            }
        }

        return $return;
    }


}