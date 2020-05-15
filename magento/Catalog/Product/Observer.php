<?php

/* Copyright (C) Youbility Software - All Rights Reserved
 * www.youbility.de
 * Unauthorized copying of this file, via any medium is strictly prohibited
 * Proprietary and confidential
 */

class Semknox_ProductSearch_Model_Catalog_Product_Observer
{

    protected $_helper;

    protected $_storesCredentials = array();

    public function __construct()
    {
        /** @var Semknox_ProductSearch_Helper_Data $helper */
        $this->_helper = Mage::helper('semknoxps');

        //get all stores with api credentials
        $this->_storesCredentials = $this->_helper->getAllStoresCredentials();
    }


    public function updateProduct($observer)
    {
        foreach($this->_storesCredentials as $storeId => $store)
        {
            $this->_updateProduct($observer, $storeId);
        }
    }

    public function deleteProduct($observer)
    {
        foreach($this->_storesCredentials as $storeId => $store)
        {
            $this->_updateProduct($observer, $storeId, true);
        }
    }

    /**
     * Catches any Product-Create/Edit/Move/... Event all over the System, gets the
     * affected Products and calls the ProductManager.
     *
     * @param Varien_Event_Observer $observer
     */
    protected function _updateProduct($observer, $storeId, $delete = false)
    {
        /**
         * @var Semknox_ProductSearch_Model_ProductManager $productManager
         */
        $event = $observer->getEvent();
        $productManager = Mage::getModel('semknoxps/productManager', $storeId);
        $productHelper = Mage::helper('catalog/product');
        $productIdsToUpdate = array();
        $productIdsToDelete = array(); // products the are edited to not visible in search

        // if Product upstream is disabled, dont do anything
        if($this->_helper->semknoxDisableProductUpstream())  return;

        $productIds = false;

        // edit one product
        if($product = $event->getProduct())
        {
            $productIds = array($product->getId());
        } else {
            $productIds = $event->getProductIds();
        }


        // edit products
        if(is_array($productIds))
        {

            if(!$delete)
            {

                // get visible in search products
                $productsVisible = Mage::getModel('catalog/product')
                                       ->getCollection()
                                       ->addAttributeToSelect('id')
                                        //->addAttributeToFilter(
                                        //  'visibility', array('in'=> Mage::getSingleton('catalog/product_visibility')->getVisibleInSearchIds())
                                        //)
                                        // -> pass all products to productManager (configurable's simple products will be passed to semknox even if they are "einzeln ncisichtbar")
                                        ->addAttributeToFilter(
                                           'entity_id', array('in' => $productIds)
                                       )->addAttributeToFilter(
                                            'status', array('in'=> Mage::getSingleton('catalog/product_status')->getVisibleStatusIds())
                                        );

                // check if products still in stock
                Mage::getSingleton('cataloginventory/stock')->addInStockFilterToCollection($productsVisible);

                $productIdsToDelete = $productIds;
                foreach($productsVisible as $p)
                {
                    $productIdsToDelete = array_diff($productIdsToDelete, array($p->getId()));
                    $productIdsToUpdate[] = $p->getId();
                }


            } else {

                $productIdsToDelete = $productIds;
            }


            $success =  array();
            $failed = array();


            // add update products to queue
            foreach($productIdsToUpdate as $productId){
                $status = $this->_helper->addProductToUpdateQueue($productId);

                if($status){
                    $success[] = $productId;
                } else {
                    $failed[] = $productId;
                }
            }

            // add delete products to queue
            foreach($productIdsToDelete as $productId) {
                $status = $this->_helper->addProductToUpdateQueue($productId, 'delete');

                if($status){
                    $success[] = '-'.$productId;
                } else {
                    $failed[] = '-'.$productId;
                }
                // "Minus"/"-" means product was noticed to delete
            }


            $log = '['.$event->getName().'] added to update queue ';


            if(count($success))
            {
                $logLevel = 6;
                $this->_helper->log($log. 'array('.implode(',',$success).') : success', $logLevel, false, $storeId);
            }

            if(count($failed))
            {
                $logLevel = 3;
                $this->_helper->log($log. 'array('.implode(',',$failed).'): failed', $logLevel, false, $storeId);
            }

        }

    }

}