<?php namespace Semknox\Core\Services\Search;

abstract class ResultItemFactory
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

    // @deprecated
    private function getProductSeparated($productData)
    {
        if(count($productData) === 1) {
            return new Product\Simple($productData);
        }
        else {
            // if all groupId are the same, it's a variation, otherwise a bundle
            $isVariation = true;
            $groupId = null;

            foreach($productData as $variation) {
                if($groupId === null) {
                    $groupId = $variation['groupId'];
                }

                if($variation['groupId'] !== $groupId) {
                    $isVariation = false;
                    break;
                }
            }

            return $isVariation
                ? new Product\Variation($productData)
                : new Product\Bundle($productData);
        }
    }
}