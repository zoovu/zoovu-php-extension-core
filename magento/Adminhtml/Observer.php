<?php

/* Copyright (C) Youbility Software - All Rights Reserved
 * www.youbility.de
 * Unauthorized copying of this file, via any medium is strictly prohibited
 * Proprietary and confidential
 */

class Semknox_ProductSearch_Model_Adminhtml_Observer
{

    protected $_helper;

    public function __construct()
    {
        $this->_helper = Mage::helper('semknoxps');
    }

    public function validateSemknoxConfig()
    {
        // is done everytime the Config-Page-Section of Semknoxps is shown -> validateSemknoxConfigOnStart
        //$this->checkApiAuthentication();

        $this->initialProductUpload();

    }


    public function validateSemknoxConfigOnStart(Varien_Event_Observer $observer)
    {
        /* @var $block Mage_Core_Block_Abstract */

        /**
         * Empty... maybe later needed
         *
        $block = $observer->getBlock();
        $controllerName = Mage::app()->getRequest()->getControllerName();
        $isBackend = Mage::app()->getStore()->isAdmin();
        $currentSection = Mage::app()->getRequest()->getParam('section');

        if ($block->getModuleName() == 'Mage_Adminhtml' && $block->getBlockAlias() == 'head' && $controllerName == 'system_config' && $isBackend && isset($currentSection) && $currentSection == 'semknoxps')
        {
            //...
        }
         *
         */
    }


    public function initialProductUpload()
    {
        /**
         * @var Semknox_ProductSearch_Helper_Data $helper
         */
        $storeId = $this->_helper->getCurrentStoreId();

        // if Product upstream is disabled, dont do anything
        if($this->_helper->semknoxDisableProductUpstream($storeId))  return;

        $initialUpload = Mage::getStoreConfig('semknoxps/initialupload/start', $storeId);

        if($initialUpload == '1')
        {
            /**
             * @var Semknox_ProductSearch_Model_ProductManager $productManager
             * @var Semknox_ProductSearch_Helper_Data $helper
             */

            $productManager = Mage::getSingleton('semknoxps/ProductManager');

            Mage::getModel('core/config')->saveConfig('semknoxps/initialupload/start', '0', 'stores', $storeId);
            Mage::app()->getStore($storeId)->resetConfig();

            $indentifier = time();

            $this->_helper->log('Initial Product Upload ['.$indentifier.'] started manually...');

            $status = $productManager->initialProductUpload();

            if($status)
            {
                $this->_helper->log('...Initial Product Upload ['.$indentifier.'] finished: SUCCESS');
                Mage::getSingleton('core/session')->addSuccess('Initial Product Upload finished.');
            }
            else
            {
                $this->_helper->log('...Initial Product Upload ['.$indentifier.'] finished: FAILED');
                Mage::getSingleton('core/session')->addError('Initial Product Upload failed.');
            }

        }

    }

}