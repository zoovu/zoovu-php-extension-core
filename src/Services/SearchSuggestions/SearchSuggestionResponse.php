<?php namespace Semknox\Core\Services\SearchSuggestions;

use Semknox\Core\Services\Search\Product;
use Semknox\Core\Services\Search\SearchResultFactory;
use Semknox\Core\Services\Traits\ArrayGetTrait;

class SearchSuggestionResponse {
    use ArrayGetTrait;

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
        $products = $this->arrayGet($this->data, 'resultGroups.Produkte');

        $result = [];

        foreach($products as $product) {
            $result[] = SearchResultFactory::getProduct($product);
        }

        return $result;
    }
}