<?php

class Semknox_ProductSearch_Model_Resource_Product_Collection extends Mage_Catalog_Model_Resource_Product_Collection
{

    public function getSize() {

        if(!isset($this->_sxProductsCount) || !$this->_sxProductsCount) return parent::getSize();

        return $this->_sxProductsCount;
    }

}
