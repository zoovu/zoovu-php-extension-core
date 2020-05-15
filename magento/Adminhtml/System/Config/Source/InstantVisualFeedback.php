<?php
/* Copyright (C) Youbility Software - All Rights Reserved
 * www.youbility.de
 * Unauthorized copying of this file, via any medium is strictly prohibited
 * Proprietary and confidential
 */


class Semknox_ProductSearch_Model_Adminhtml_System_Config_Source_InstantVisualFeedback
{

    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        return array(
            array('value' => 'all', 'label' => Mage::helper('semknoxps')->__('all')),
            array('value' => 'top', 'label' => Mage::helper('semknoxps')->__('top')),
            array('value' => 'bottom', 'label' => Mage::helper('semknoxps')->__('bottom')),
            array('value' => 'none', 'label' => Mage::helper('semknoxps')->__('none')),
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
            'all'   => Mage::helper('semknoxps')->__('all'),
            'top' => Mage::helper('semknoxps')->__('top'),
            'bottom'     => Mage::helper('semknoxps')->__('bottom'),
            'none'     => Mage::helper('semknoxps')->__('none'),
        );
    }

}
