<?php namespace Semknox\Core\Services\Search;

class Product {
    /**
     * @var array Result item data
     */
    protected $masterProduct;

    /**
     * @var Product[]
     */
    protected $groupedProducts;

    /**
     * Initialize a product object
     *
     * @param array $masterProduct
     * @param array $groupedProducts
     */
    public function __construct(array $masterProduct, array $groupedProducts=[])
    {
        $this->masterProduct = $masterProduct;
        $this->groupedProducts = $groupedProducts;
    }

    /**
     * Return the original shop identifier (usually the id in the database).
     * @return mixed.
     */
    public function getId()
    {
        return $this->masterProduct['identifier'];
    }

    /**
     * Return the id Semknox has given this product.
     * @return int
     */
    public function getSxId()
    {
        return $this->masterProduct['sxId'];
    }

    /**
     * Return if this product is grouped
     *
     * @return string
     */
    public function isGrouped()
    {
        return count($this->groupedProducts) > 1;
    }

    /**
     * Return the grouped (child-) products.
     * @return array|Product[]
     */
    public function getGroupedProducts()
    {
        return $this->groupedProducts;
    }

    /**
     * Get the link to the
     */
    public function getLink()
    {
        return $this->masterProduct['link'];
    }

    /**
     * Return the item name
     * @return string
     */
    public function getName()
    {
        return $this->masterProduct['name'];
    }

    /**
     * Return the main image for this item.
     * @return string
     */
    public function getImage()
    {
        return $this->masterProduct['image'];
    }

    /**
     * Return if this product is a featured product
     * @return bool
     */
    public function isFeatured()
    {
        return isset($this->masterProduct['featured'])
             ? $this->masterProduct['featured']
             : false;
    }

    /**
     * Return if this product is a master product (main product for variations).
     * @return bool
     */
    public function isMaster()
    {
        return isset($this->masterProduct['master'])
            ? $this->masterProduct['master']
            : false;
    }

}