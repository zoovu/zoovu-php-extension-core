<?php
/**
 * File created for semknox-core.
 * @author aselle
 * @created 2021-04-19
 */

namespace Semknox\Core\Services\Search\Interfaces;

use Semknox\Core\Services\Search\Filters\TreeFilter;
use Semknox\Core\Services\Search\Product;
use Semknox\Core\Services\Search\Sorting\SortingOption;

interface SearchResponseInterface
{
    /**
     * Return information
     * @return array
     */
    public function getInterpretedQuery();

    /**
     * Return the answer text generated by Semknox for this query.
     * @return string
     */
    public function getAnswerText();

    /**
     * Return all filter options available for this search.
     * @return TreeFilter[]
     */
    public function getAvailableFilters();

    /**
     * Return filters that are active for this query.
     * @return mixed|null
     */
    public function getActiveFilters();

    /**
     * Get available sorting options.
     *
     * @return SortingOption[]
     */
    public function getAvailableSortingOptions();

    /**
     * Return the sorting options that are active for this query.
     * @return array
     */
    public function getActiveSortingOption();

    /**
     * Return the number of total results for this search. This includes CONTENT and PRODUCT results. To return only the number of results for a specific resultGroup, pass the $resultGroup parameter. For example `->getTotalResults('products')` will return the number of product results.
     *
     * @param null $resultGroup TotalResults of which resultGroup. If null it will return the number of results for all resultGroups combined.
     *
     * @return int
     */
    public function getTotalResults($resultGroup = null);

    /**
     * Get the total number of products found for this request
     * @return int
     */
    public function getTotalProductResults();

    /**
     * Alias for `getResults('products')`.
     *
     * @return Product[]
     */
    public function getProducts();

    /**
     * Return all products. Does not group variations together. Alias for `getResults('products', true)`.
     * @return Product[]
     */
    public function getProductsFlattened();

    /**
     * Return an array of ResultItems (i.e. the products)
     *
     * @param bool $flattened Return grouped products as standalone products.
     *
     * @return Product[]
     */
    public function getResults($groupType, $flattened = false);
}