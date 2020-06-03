<?php namespace Semknox\Core\Services\Search;

abstract class ResultItem {
    /**
     * @var array Result item data
     */
    protected $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    /**
     * Return the original shop identifier (usually the id in the database).
     * @return mixed.
     */
    public function id()
    {
        return $this->data['identifier'];
    }

    /**
     * Return the id Semknox has given this product.
     * @return int
     */
    public function sxId()
    {
        return $this->data['sxId'];
    }

    /**
     * Return the product type for this result item. Possible values:
     *  - simple
     *  - variation
     *  - bundle
     *
     * @return string
     */
    public function getProductType()
    {
        return strtolower(basename(static::class));
    }


    /**
     * Get the link to the
     */
    public function link()
    {
        return $this->data['link'];
    }

    /**
     * Return the item name
     * @return string
     */
    public function name()
    {
        return $this->data['name'];
    }

    /**
     * Return the main image for this item.
     * @return string
     */
    public function image()
    {
        return $this->data['image'];
    }

    /**
     * Return if this product is a featured product
     * @return bool
     */
    public function isFeatured()
    {
        return isset($this->data['featured'])
             ? $this->data['featured']
             : false;
    }

    /**
     * Return if this product is a master product (main product for variations).
     * @return bool
     */
    public function isMaster()
    {
        return isset($this->data['master'])
            ? $this->data['master']
            : false;
    }




}