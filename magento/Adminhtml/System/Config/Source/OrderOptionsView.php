<?php
/* Copyright (C) Youbility Software - All Rights Reserved
 * www.youbility.de
 * Unauthorized copying of this file, via any medium is strictly prohibited
 * Proprietary and confidential
 */


class Semknox_ProductSearch_Model_Adminhtml_System_Config_Source_OrderOptionsView
{

    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        return array(
            array('value' => 'both', 'label' => Mage::helper('semknoxps')->__('both_sort_toolbar')),
            array('value' => 'magento', 'label' => Mage::helper('semknoxps')->__('magento_sort_toolbar')),
            array('value' => 'semknox', 'label' => Mage::helper('semknoxps')->__('semknox_sort_toolbar'))
        );
    }

    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray()
    {
        return array(
            'both'   => Mage::helper('semknoxps')->__('both_sort_toolbar'),
            'magento' => Mage::helper('semknoxps')->__('magento_sort_toolbar'),
            'semknox'     => Mage::helper('semknoxps')->__('semknox_sort_toolbar'),
        );
    }

}
