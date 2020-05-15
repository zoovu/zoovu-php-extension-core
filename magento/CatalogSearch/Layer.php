<?php

class Semknox_ProductSearch_Model_CatalogSearch_Layer extends Mage_Catalog_Model_Layer
{

    protected $_semknox_message = '';
    protected $_semknox_has_results = false;

    protected $_semknox_productCollection;

    protected $_semknox_productResult;

    protected $_semknox_productResultFilters;

    protected $_collection_has_products = true;

    public $_api_limit;


    public function getSemknoxMessage()
    {
        return $this->_semknox_message;
    }


    public function getSemknoxHasResults()
    {
        return $this->_semknox_has_results;

    }

    public function getCollectionHasProducts()
    {
        return $this->_collection_has_products;

    }

    /**
     * Retrieve current layer product collection
     *
     * @return Mage_Catalog_Model_Resource_Eav_Mysql4_Product_Collection
     */
    public function getProductCollection()
    {
        $collection = $this->getSemknoxProductCollection();

        if($collection->getSize() == 0)
        {
            $this->_collection_has_products = false;
        }

        $this->prepareProductCollection($collection);

        $collection->load();

        return $collection;
    }

    public function getSemknoxFilters()
    {

        if(!is_null($this->_semknox_productResultFilters)) return $this->_semknox_productResultFilters;

        $this->getSemknoxProductCollection();

        return $this->_semknox_productResultFilters;

    }



    protected function getSemknoxProductCollection()
    {

        if(!is_null($this->_semknox_productCollection)) return $this->_semknox_productCollection;

        /**
         * @var Semknox_ProductSearch_Model_ProductManager $productManager
         * @var Semknox_ProductSearch_Helper_Data $helper
         * @var Mage_Core_Controller_Request_Http $request
         */

        $productManager = Mage::getSingleton('semknoxps/ProductManager');
        $helper = Mage::helper('semknoxps');
        $request = Mage::app()->getRequest();

        $query = $request->getParam('q');
        $order = $request->getParam('order', 'null');
        $direction = $request->getParam('dir', 'asc');
        $page = $request->getParam('p', 1);

        $filters = $this->getCurrentFilters($request);

        $productManager->setFilter($filters);
        $productManager->setOrder($order, $direction);
        $productManager->setPage($page);

        //todo: improve!
        $toolbarBlock = Mage::getBlockSingleton('catalog/product_list_toolbar');
        if($toolbarBlock && $toolbarBlock->getLimit() && !$helper->semknoxSeoClientSideSearchIsActive()){
            $limit = $toolbarBlock->getLimit();
        } else {
            $limits = Mage::getStoreConfig('catalog/frontend/grid_per_page_values').','.Mage::getStoreConfig('catalog/frontend/list_per_page_values');
            $limits = explode(',',$limits);
            $limit = max($limits);
        }
        $productManager->setLimit($limit);

        try {
            /** @var Semknox_ProductSearch_Model_ProductResult  $apiResponse */
            $apiResponse = $productManager->getProducts($query); // we just need the ids -> when implemented use getProductIds()
        }
        catch(Zend_Http_Client_Exception $e) {
            // timeout occurred
            $helper->log('API TIMEOUT: '. get_class());

            // redirect to default magento search with parameter _swtw set to current timestamp
            // if _swtw is set Semknox_ProductSearch_Model_Html_Observer will not redirect
            // magento search requests to semknox

            $redirectUrl = Mage::getUrl('catalogsearch/result', array(
                '_query' => array(
                    'q' => $query,
                    '_swtw' => time()
                )
            ));

            Mage::app()->getResponse()
                ->setRedirect( $redirectUrl, 302)
                ->sendResponse();
        }

        // save for later use
        $this->_semknox_productResult = $apiResponse;
        $this->_semknox_productResultFilters = $apiResponse->getFilters();
        $this->_api_limit = $productManager->getLimit();


        // redirect if query mapping is set in Semknox backend
        if($redirectUrl = $apiResponse->getRedirectUrl())
        {
            Mage::app()->getResponse()
                ->setRedirect($redirectUrl, 301)
                ->sendResponse();
        }

        // message management
        if($explanation = $apiResponse->getQueryExplanation())
        {
            $this->_semknox_message = $explanation;
        }


        // check if response has results
        if($qty = $apiResponse->getNumberOfProducts())
        {
            //$this->_semknox_has_results = true;
            $this->_semknox_has_results = $qty;
        }


        //get Product Ids
        $productIds = $apiResponse->getMagentoProductIds();
        $this->productIds = $productIds; //for debug mode

        if($this->_semknox_has_results && !empty($productIds)) {

            $collection = Mage::getModel('catalog/product')
                              ->getCollection()
                              ->addAttributeToFilter('entity_id', array('in', $productIds));

            $collection->getSelect()->order(new Zend_Db_Expr('FIELD(e.entity_id, ' . implode(',', $productIds).')'));

        } else {

            $this->_semknox_has_results = false;

            $collection = Mage::getModel('catalog/product')
                              ->getCollection()
                              //->addAttributeToSelect('*')
                              ->addAttributeToFilter('entity_id', 0);

        }

        $collection->_sxProductsCount = $apiResponse->getNumberOfProducts();

        $this->_semknox_productCollection = $collection;

        return $collection;

    }


    /**
     * Initialize product collection
     *
     * @param Mage_Catalog_Model_Resource_Eav_Mysql4_Product_Collection $collection
     * @return Mage_Catalog_Model_Layer
     */
    public function prepareProductCollection($collection)
    {
        $collection
            ->addAttributeToSelect(Mage::getSingleton('catalog/config')->getProductAttributes())
            ->addMinimalPrice()
            ->addFinalPrice()
            ->addTaxPercents()
            ->addUrlRewrite($this->getCurrentCategory()->getId())
            ;

        //$collection->addAttributeToFilter('visibility', array('in'=>Mage::getSingleton('catalog/product_visibility')->getVisibleInSearchIds()));
        //$collection->addAttributeToFilter('status', array('in'=> Mage::getSingleton('catalog/product_status')->getVisibleStatusIds()));

        return $this;
    }


    public function getOrders()
    {
        $orders = $this->_semknox_productResult->getOrders();

        return $orders;
    }


    public function getFilters()
    {
        $filters = $this->getSemknoxFilters();

        return $filters;
    }

    public function getCurrentFilters($request)
    {
        $query = $request->getParams();
        if(!isset($query['filter'])) return array();
        return $query['filter'];

    }


}
