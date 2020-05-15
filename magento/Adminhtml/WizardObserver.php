<?php

/* Copyright (C) Youbility Software - All Rights Reserved
 * www.youbility.de
 * Unauthorized copying of this file, via any medium is strictly prohibited
 * Proprietary and confidential
 */

class Semknox_ProductSearch_Model_Adminhtml_WizardObserver
{

    protected $_currentStoreId;

    protected $_currentStep;

    protected $_requiredDemoInputs = array('firstname', 'lastname','company','street','streetNr','zip', 'city','email', 'telefon','storeUrl', 'storeId', 'storeSystem', 'storeSystemVersion', 'storeLanguage','agbAccepted');

    //protected $numberOfSteps = 5;

    protected $_stepFinishedCache = array();

    protected $_helper;

    protected $_validateWizardDone = false;


    public function __construct()
    {
        /**
         * @var Semknox_ProductSearch_Helper_Data $helper
         */
        $this->_helper = Mage::helper('semknoxps');

        $this->_currentStoreId = $this->_helper ->getCurrentStoreId();
        $this->_currentStep = $this->_helper ->getStoreConfig('semknoxps_wizard/general/currentStep', $this->_currentStoreId, 1);
    }


    public function onSave()
    {
        $this->setCurrentWizardStep();
    }


    /**
     * is called everytime the Config-Page-Section of Semknoxps (and Semknox Wizard) is shown
     */
    public function onEveryLoad(Varien_Event_Observer $observer)
    {
        /* @var $block Mage_Core_Block_Abstract */

        if ( !$this->_validateWizardDone && Mage::app()->getRequest()->getParam('section') == 'semknoxps_wizard')
        {
            $this->validateWizard();
            $this->_validateWizardDone = true;
        }
    }

    public function validateWizard()
    {

        /** if scope not selected, redirect to default store */
        if($this->_currentStoreId === 0)
        {
            $stores = $this->_helper->getAllStores();

            foreach($stores as $storeId => $store)
            {
                if($store['isDefault'])
                {
                    $url = Mage::getSingleton('adminhtml/url')->getUrl('*/system_config/edit/section/semknoxps_wizard/store/'.$store['code']);

                    Mage::app()->getResponse()
                        ->setRedirect($url, 301)
                        ->sendResponse();
                }
            }
        }

        /**
         * more things to do...
         */
        if($this->_helper->getSemknoxCredentialsStatus() !== 1)
        {
            // reset wizard
            $this->setCurrentWizardStep(1);
        }
    }

    public function setCurrentWizardStep($step = false)
    {
        if($step || $this->stepFinished())
        {
            if(!$step && !is_bool($this->stepFinished()))
            {
                $step = $this->stepFinished();
            }
            elseif(!$step)
            {
                $step = $this->_currentStep + 1;
            }

            $this->_helper->setCurrentWizardStep($step, $this->_currentStoreId);
        }

    }


    public function stepFinished()
    {
        $method = 'validateStep'.$this->_currentStep;

        if(isset($this->_stepFinishedCache[$method])) return $this->_stepFinishedCache[$method];


        if(method_exists($this, $method))
        {
            $finished = $this->$method();
        } else {
            $finished = true;
        }

        $this->_stepFinishedCache[$method] = $finished;
        return $finished;
    }


    public function validateStep1()
    {
        $setAccountData = false;

        $accountTyp = $this->_helper->getStoreConfig('semknoxps_wizard/step1/account', $this->_currentStoreId, 'existing');


        $stepData = Mage::getStoreConfig('semknoxps_wizard/step1', $this->_currentStoreId);


        if($accountTyp == 'existing')
        {
            $apiKey = ( isset($stepData['apiKey']) ) ? $stepData['apiKey'] : '';
            $customerID = ( isset($stepData['customerID']) ) ? $stepData['customerID'] : '';

            if(strlen($apiKey) == 0 || strlen($customerID) == 0)
            {
                Mage::getSingleton('core/session')->addError($this->_helper->__("Required Field '%s' missing or invalid", 'apiKey\' or \'customerID'));
                return false;
            }

            /**
             * @var Semknox_ProductSearch_Model_AuthenticationManager $authManager
             */
            $authManager = Mage::getModel('semknoxps/authenticationManager');

            if(!$authManager->checkCredentials($customerID, $apiKey))
            {
                $error = $authManager->getErrorMessage();
                Mage::getSingleton('core/session')->addError($error);
                return false;
            } else {
                $setAccountData = true;
            }

        } else {
            // $accountTyp = new

            $stepData['storeId'] = $this->_currentStoreId;
            $stepData['storeSystem'] = 'Magento';
            $stepData['storeSystemVersion'] = Mage::getVersion();
            $stepData['storeLanguage'] = Mage::getStoreConfig('general/locale/code', $this->_currentStoreId);

            if(!isset($stepData['agbAccepted']) || strlen($stepData['agbAccepted']) == 0)
            {
                unset($stepData['agbAccepted']);
            }

            $requiredInputs = $this->_requiredDemoInputs;
            $requiredInputsData = $stepData;

            foreach($requiredInputs as $input)
            {
                if(isset($requiredInputsData[$input]) && strlen($requiredInputsData[$input]) > 0)
                {
                    $requiredInputs = array_diff($requiredInputs, array($input));
                } else {
                    $field = Mage::helper('semknoxps')->__($input);
                    Mage::getSingleton('core/session')->addError(Mage::helper('semknoxps')->__("Required Field '%s' missing or invalid", $field));
                }
            }

            if(count($requiredInputs)) return false;


            return $this->getDemoAccess($stepData);
        }


        if($setAccountData)
        {
            Mage::getModel('core/config')->saveConfig('semknoxps_wizard/step1/apiKey', null, 'stores', $this->_currentStoreId);
            Mage::getModel('core/config')->saveConfig('semknoxps_wizard/step1/customerID', null, 'stores', $this->_currentStoreId);
            Mage::getModel('core/config')->saveConfig('semknoxps_wizard/step1/account', null, 'stores', $this->_currentStoreId);

            Mage::getModel('core/config')->saveConfig('semknoxps/login/customerId', $customerID, 'stores', $this->_currentStoreId);
            Mage::getModel('core/config')->saveConfig('semknoxps/login/apiKey', $apiKey, 'stores', $this->_currentStoreId);

            Mage::app()->getStore($this->_currentStoreId)->resetConfig();

            return 2;
        }

        return false;
    }

    public function validateStep2()
    {
        return '2done';
    }


    public function validateStep2done()
    {
        return 3;
    }

    public function validateStep3()
    {
        $accountManager = Mage::getModel('semknoxps/AccountManager');

        $status = $accountManager->getAccountStatus();

        if($status['status'] == 'SEARCH_READY') {
            return 4;
        }

        return false;
    }

    public function validateStep4()
    {
        return 5;
    }



    public function getDemoAccess($stepData)
    {

        /**
         * @var Semknox_ProductSearch_Model_AuthenticationManager $authManager
         */
        $authManager = Mage::getModel('semknoxps/authenticationManager');

        unset($stepData['account']);
        unset($stepData['customerID']);
        unset($stepData['apiKey']);

        $preparedData = array(
            'shopUrl'           => $stepData['storeUrl'],
            'language'          => substr($stepData['storeLanguage'],0,2),
            'shopId'            => $stepData['storeId'],
            'moreInformation'   => $stepData,
        );

        $this->_helper->log('Demo Access to: '.json_encode($preparedData), true, $this->_currentStoreId);

        if($demoData = $authManager->requestDemoKey( $preparedData['shopUrl'], $preparedData))
        {
            $apiKey = $demoData['testKey'];
            $customerId = $demoData['customerId'];

            Mage::getModel('core/config')->saveConfig('semknoxps/login/customerId', $customerId, 'stores',  $this->_currentStoreId);
            Mage::getModel('core/config')->saveConfig('semknoxps/login/apiKey', $apiKey, 'stores',  $this->_currentStoreId);
            Mage::getModel('core/config')->saveConfig('semknoxps_wizard/step1/account', 'existing', 'stores',  $this->_currentStoreId);

            Mage::getSingleton('core/session')->addSuccess('Demo Access created.');

            $delete = $this->_requiredDemoInputs;
            $delete[] = 'agb';
            foreach($delete as $input)
            {
                Mage::getModel('core/config')->saveConfig('semknoxps_wizard/step1/'.$input, '', 'stores',  $this->_currentStoreId);
            }
            Mage::app()->getStore( $this->_currentStoreId)->resetConfig();

            $this->_helper->log('Demo Access: SUCCESS', true, $this->_currentStoreId);
            return true;

        } else {

            $error = $authManager->getErrorMessage();
            Mage::getSingleton('core/session')->addError($error);

            $this->_helper->log('Demo Access: FAILED - '.$error, true, $this->_currentStoreId);
            return false;

        }


    }
}