<?php namespace Semknox\Core\Services\Search;

abstract class ResultItemFactory
{
    /**
     * Get a instance of Product\Simple, Product\Bundle or Product\Variation
     * @param $productData
     * @return Product\Bundle|Product\Simple|Product\Variation
     */
    public static function getProduct($productData)
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