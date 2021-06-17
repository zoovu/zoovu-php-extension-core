<?php namespace Semknox\Core\Services\Search;

use Semknox\Core\Exceptions\LogicException;
use Semknox\Core\Services\Search\Filters\CollectionFilter;
use Semknox\Core\Services\Search\Filters\BucketFilter;
use Semknox\Core\Services\Search\Filters\RangeFilter;
use Semknox\Core\Services\Search\Filters\TreeFilter;
use Semknox\Core\Services\Search\Sorting\SortingOption;

/**
 * The purpose of this class is to convert the response from the Semknox API
 * from JSON (array) to concrete class implementations.
 * Instead of returning
 *
 * @package Semknox\Core\Services\Search
 */
abstract class SearchResultFactory
{
    /**
     * Get a instance of Product\Simple, Product\Bundle or Product\Variation
     * @param $productData
     * @return Product
     */
    public static function getProduct($productData)
    {
        if(count($productData) === 1) {
            return new Product($productData[0]);
        }
        else {
            $master = [];
            $childs = [];

            foreach($productData as $item) {
                if(isset($item['master']) && $item['master']) {
                    $master = $item;
                } else {
                    $childs[] = self::getProduct([$item]);
                }
            }

            // if master is not set, use first child as master
            if(!$master) {
                $master = $productData[0];
                array_shift($childs);
            }

            return new Product($master, $childs);
        }
    }


    /**
     * Get a instance of Content\Custom
     * @param $contentData
     * @return Content
     */
    public static function getContent($sectionData)
    {
        $content = [];

        foreach ($sectionData['results'] as $item) {
            $content[] = new Content($sectionData, $item);
        }

        return $content;
    }

    /**
     * Create a filter object.
     * @param array $filterData
     */
    public static function getFilter(array $filterData, array $activeFilters)
    {
        $filterType = isset($filterData['type']) ? $filterData['type'] : false;
        $filterType = !$filterType && isset($filterData['filterType']) ? $filterData['filterType'] : $filterType;
        $filter = false;

        switch($filterType) {
            case 'TREE':
                $filter = new TreeFilter($filterData);
                break;

            case 'RANGE':
                $filter = new RangeFilter($filterData);
                break;

            case 'COLLECTION':
                $filter = new CollectionFilter($filterData);
                break;

            case 'BUCKET':
                $filter = new BucketFilter($filterData);
                break;
        }

        if(!$filter) {
            return false;
            /*
            $exceptionMessage = sprintf('Undefined filter type "%s" received.', $filterType);
            throw new LogicException($exceptionMessage);
            */
        }

        // set active or not
        // sporadic (???): 
        // main.CRITICAL: Notice: Undefined index: key in .../semknox-core/src/Services/Search/SearchResultFactory.php on line 75 [] []
        $activeFilterKeys = array_map(function($filter) {
            return isset($filter['key']) ? $filter['key'] : false;
        }, $activeFilters);
        $activeFilterKeys = array_filter($activeFilterKeys);

        if(($key = array_search($filter->getId(), $activeFilterKeys)) !== false) {
            $filter->setActive(true);

            if(isset($activeFilters[$key]['values'])) {
                $filter->setActiveOptions($activeFilters[$key]['values']);
            }
            else {
                // range filter does not have ['values'] but ['min'] and ['max']
                $filter->setActiveOptions([
                    $activeFilters[$key]['min'],
                    $activeFilters[$key]['max']
                ]);

            }

        }

        return $filter;
    }

    /**
     * Create a sorting option object.
     * @param $option
     * @param array $activeSort
     */
    public static function getSortingOption($option, array $activeSort)
    {
        return new SortingOption($option);
    }
}