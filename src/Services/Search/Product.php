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
     * Return the GroupId 
     * @return mixed.
     */
    public function getGroupId()
    {
        return $this->masterProduct['groupId'];
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
     * Return the Category Semknox has given this product.
     * @return string
     */
    public function getSxCategoryId()
    {
        return isset($this->masterProduct['sxCategoryId']) ? $this->masterProduct['sxCategoryId'] : null;
    }

    /**
     * Return if this product is grouped
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
     * @return string
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
     * Return the item relevance
     * @return int
     */
    public function getRelevance()
    {
        return (int) $this->masterProduct['relevance'];
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
     * Return the images for this item.
     * 
     * @param string $type
     * @return string
     */
    public function getImages(string $type = '')
    {
        $images = (isset($this->masterProduct['images']) && is_array($this->masterProduct['images'])) ? $this->masterProduct['images'] : [];
        if(!$type) return $images;

        foreach($images as $key => $image){
            if($image['type'] != $type) unset($images[$key]);
        }
        return $images;
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

    /**
     * Return if this product is head.
     * @return bool
     */
    public function isHead()
    {
        return isset($this->masterProduct['head'])
        ? $this->masterProduct['head']
        : false;
    }


    /**
     * Return the datapoints of this product.
     * 
     * @return array
     */
    public function getDataPoints()
    {
        return (isset($this->masterProduct['dataPoints']) && is_array($this->masterProduct['dataPoints'])) ? $this->masterProduct['dataPoints'] : [];
    }

    /**
     * Return specific datapoint of this product by key.
     * 
     * @param string $key
     * @return mixed
     */
    public function getDataPoint(string $key)
    {
        $dataPoints = $this->getDataPoints();

        foreach($dataPoints as $dataPoint){
            if(isset($dataPoint['key']) && $dataPoint['key'] == $key) return $dataPoint;
        }

        return false;
    }

    /**
     * Return specific datapoint of this product by id.
     * 
     * @param string $id
     * @return mixed
     */
    public function getDataPointById(string $id)
    {
        $dataPoints = $this->getDataPoints();

        foreach ($dataPoints as $dataPoint) {
            if (isset($dataPoint['id']) && $dataPoint['id'] == $id) return $dataPoint;
        }

        return false;
    }

}