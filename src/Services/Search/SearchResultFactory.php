<?php namespace Semknox\Core\Services\Search;

use Semknox\Core\Exceptions\LogicException;
use Semknox\Core\Services\Search\Filters\CollectionFilter;
use Semknox\Core\Services\Search\Filters\RangeFilter;
use Semknox\Core\Services\Search\Filters\TreeFilter;
use Semknox\Core\Services\Search\Sorting\SortingOption;

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
     * Create a filter object.
     * @param array $filterData
     */
    public static function getFilter(array $filterData, array $activeFilters)
    {
        switch(strtoupper($filterData['type'])) {
            case 'TREE':
                return new TreeFilter($filterData);

            case 'RANGE':
                return new RangeFilter($filterData);

            case 'COLLECTION':
                return new CollectionFilter($filterData);
        }

        $exceptionMessage = sprintf('Undefined filter type "%s" received.', $filterData['type']);
        throw new LogicException($exceptionMessage);
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