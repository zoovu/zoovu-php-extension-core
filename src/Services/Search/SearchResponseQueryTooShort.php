<?php

namespace Semknox\Core\Services\Search;

use Semknox\Core\Services\Search\Filters\TreeFilter;
use Semknox\Core\Interfaces\SearchResponseInterface;
use Semknox\Core\Services\Search\Sorting\SortingOption;
use Semknox\Core\Services\Traits\ArrayGetTrait;

/**
 * The response when the given query was too short
 * @package Semknox\Core\Services\Search
 */
class SearchResponseQueryTooShort implements SearchResponseInterface
{
    use ArrayGetTrait;

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
        return 'Der eingegebene Suchbegriff ist zu kurz. Wir konnte keine Ergebnisse finden.';
    }

    /**
     * Return all filter options available for this search.
     * @return TreeFilter[]
     */
    public function getAvailableFilters()
    {
        return [];
    }

    /**
     * Return filters that are active for this query.
     * @return mixed|null
     */
    public function getActiveFilters()
    {
        return [];
    }

    /**
     * Get available sorting options.
     *
     * @return SortingOption[]
     */
    public function getAvailableSortingOptions()
    {
        return [];
    }

    public function getActiveSortingOption()
    {
        return [];
    }

    /**
     * Return the number of total results for this search. This includes CONTENT and PRODUCT results. To return only the number of results for a specific resultGroup, pass the $resultGroup parameter. For example `->getTotalResults('products')` will return the number of product results.
     *
     * @param null $resultGroup TotalResults of which resultGroup. If null it will return the number of results for all resultGroups combined.
     * @return int
     */
    public function getTotalResults($resultGroup=null)
    {
        return 0;
    }

    /**
     * Get the total number of products found for this request
     * @return int
     */
    public function getTotalProductResults()
    {
        return 0;
    }

    /**
     * Alias for `getResults('products')`.
     * @return Product[]
     */
    public function getProducts()
    {
        return [];
    }

    /**
     * Return all products. Does not group variations together. Alias for `getResults('products', true)`.
     * @return Product[]
     */
    public function getProductsFlattened()
    {
        return [];
    }

    /**
     * Return an array of ResultItems (i.e. the products)
     * @param bool $flattened Return grouped products as standalone products.
     * @return Product[]
     */
    public function getResults($groupType, $flattened=false)
    {
        return [];
    }

    /**
     * Return the searchResult-group with the given type. The type can be "products" or "content".
     * @param $groupType
     * @param $key Which key to return from the found searchResultGroup. Returns the whole resultGroup when no key is specified.
     * @return array
     */
    private function getResultGroup($groupType, $key=null)
    {
        return [];
    }


}