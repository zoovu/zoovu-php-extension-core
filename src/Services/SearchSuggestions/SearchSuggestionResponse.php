<?php namespace Semknox\Core\Services\SearchSuggestions;

use Semknox\Core\Services\Search\Product;
use Semknox\Core\Services\Search\ResultItemFactory;

class SearchSuggestionResponse {
    protected $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    /**
     * Get suggested products
     * @return Product[]
     */
    public function getProducts()
    {
        var_dump($this->data);

        $products = isset($this->data['suggests']['Products'])
            ? $this->data['suggests']['Products']
            : [];

        $result = [];

        foreach($products as $product) {
            $result[] = ResultItemFactory::getProduct($product);
        }

        return $result;
    }
}