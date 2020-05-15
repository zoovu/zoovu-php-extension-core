<?php

class Semknox_ProductSearch_Model_Adminhtml_System_Config_Source_AttributeMatching
    extends Mage_Adminhtml_Block_System_Config_Form_Field_Array_Abstract
{

    /**
     * Prepare to render
     */
    protected function _prepareToRender()
    {
        $this->addColumn('magento', array(
            'label' => Mage::helper('semknoxps')->__('Magento Attr.-Code'),
            'style' => 'width: 100px',
        ));

        $this->addColumn('api', array(
            'label' => Mage::helper('semknoxps')->__('API Attr.-Code'),
            'style' => 'width: 100px'
        ));


        $this->setTemplate('semknoxps/system/config/form/field/array.phtml');
        $this->_addButtonLabel = Mage::helper('semknoxps')->__('Add Pair');
    }

}