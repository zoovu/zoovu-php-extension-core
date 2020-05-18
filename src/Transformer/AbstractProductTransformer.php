<?php namespace Semknox\Core\Transformer;

abstract class AbstractProductTransformer {

    protected $product;

    public function __construct($product)
    {
        $this->product = $product;
    }

    /**
     *
     * @param array $parameters
     *
     * @return array
     */
    abstract public function transform($parameters = []);

}