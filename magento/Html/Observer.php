<?php

/* Copyright (C) Youbility Software - All Rights Reserved
 * www.youbility.de
 * Unauthorized copying of this file, via any medium is strictly prohibited
 * Proprietary and confidential
 */

class Semknox_ProductSearch_Model_Html_Observer
{

    protected $_setRobotsDone = false;

    //--------------------------------------------------------------
    // Core Block Abstract To HTML Before
    //--------------------------------------------------------------

    public function coreBlockAbstractToHtmlBefore($observer)
    {
        if (!$this->_setRobotsDone && $observer->getBlock() &&  $observer->getBlock()->getBlockAlias() == 'head')
        {
            //TODO fix...always called
            //$this->_setRobots();
            $this->_setRobotsDone = true;
        }
    }

    /**
     * Sets meta="robots" to noindex, nofollow if that option is activated
     *
     * @return null
     */
    protected function _setRobots()
    {
        /* @var Semknox_ProductSearch_Helper_Data $helper */
        $helper = Mage::helper('semknoxps');

        if($helper->semknoxSearchResultToNoIndex()){

            Mage::app()->getLayout()->getBlock('head')->setRobots('noindex,follow');
        }
    }




    //--------------------------------------------------------------
    // controllerActionLayoutLoadBefore
    //--------------------------------------------------------------

    public function controllerActionLayoutLoadBefore($observer)
    {
        // redirect /catalogsearch/result/?q= to /semknoxps/?q=
        $this->_redirectOldSearchUrls();

        // shows search page if no route was found (404)
        $this->_enableSeoUrl($observer);
    }





    protected function _enableSeoUrl($observer)
    {
        /* @var Semknox_ProductSearch_Helper_Data $helper */
        $helper = Mage::helper('semknoxps');

        if($helper->semknoxpsIsActive() && $helper->semknoxSeoUrlIsActive())
        {
            // 1) if ?q is set redirect to seo url.
            $this->_redirectToSeoUrlIfQuerySet();


            if($this->_isNoRoute()) {

                // 2) if no ?q is set use url as query parameter
                $this->_useCurrentUrlAsSearchParameter();


                // 3) set layout
                $layout = $observer->getEvent()->getLayout();
                $layout->getUpdate()->addHandle( 'semknoxps_index_index_handle' );
                $layout->generateXml();

            }

        }
        // redirect to magento search route if seo urls are active
        else if($helper->semknoxSeoUrlIsActive() && $this->_isNoRoute()) {

            $url = Mage::getUrl('catalogsearch/result', array(
                '_query' => array(
                    'q' => $helper->getLastDirectoryInUrl()
                )
            ));

            Mage::app()->getResponse()
                ->setRedirect($url, 301)
                ->sendResponse();
        }
    }


    protected function _useCurrentUrlAsSearchParameter( )
    {
        /* @var Semknox_ProductSearch_Helper_Data $helper */
        $helper = Mage::helper('semknoxps');

        $lastDirectoryInUrl = $helper->getLastDirectoryInUrl();

        // if we are not on a search module page (e.g. /semknoxps/) set param q
        if($lastDirectoryInUrl != $helper->getResultsUrl())
        {
            $query = $this->_decodeQueryParameter($lastDirectoryInUrl);

            Mage::app()->getRequest()->setParam('q', $query);
        }
    }


    /**
     * Redirect to /my+search+query  if parameter ?q is set
     *
     * @return null
     */
    protected function _redirectToSeoUrlIfQuerySet()
    {
        $query = Mage::app()->getRequest()->getParam('q');

        if($query)
        {
            $query = $this->_encodeQueryParameter($query);

            Mage::app()->getResponse()
                ->setRedirect(Mage::getBaseUrl() . $query, 301)
                ->sendResponse();
        }
    }

    /**
     * Decodes a query parameter:
     * "rotes+ding"        => "rotes ding"
     * "WEI%C3%9FE+SACHEN" => WEIßE SACHEN"
     *
     * @param $query
     *
     * @return string
     */
    protected function _decodeQueryParameter( $query )
    {

        $query = explode('+', $query);

        foreach($query as $key => $value) $query[$key] = urldecode($value);

        $query = implode('+', $query);

        return str_replace('+',' ', $query);
    }

    /**
     * Encodes a query parameter:
     *   "rotes ding" => "rotes+ding"
     *   "WEIßE SACHEN" => "WEI%C3%9FE+SACHEN"
     *
     * @param $query
     *
     * @return string
     */
    protected function _encodeQueryParameter( $query )
    {
        $query = explode(' ', $query);

        foreach($query as $key => $value) $query[$key] = urlencode($value);

        $query = implode(' ', $query);

        return str_replace(' ','+', $query);
    }

    /**
     * Check if no route was found (error 404)
     *
     * @return bool
     */
    protected function _isNoRoute()
    {
        $moduleName = Mage::app()->getRequest()->getModuleName();
        $actionName = Mage::app()->getRequest()->getActionName();
        $controllerName = Mage::app()->getRequest()->getControllerName();

        return ($moduleName == 'cms' && $actionName == 'noRoute' && $controllerName == 'index');
    }



    /**
     * Redirect magento search url to new Semknox search url (if Semknox search is active).
     *   Example 1: /catalogsearch/result/?q=Bla        => /semknoxps/?q=Bla
     *   Example 2: /catalogsearch/advanced/?name=Bla        => /semknoxps/?q=Bla
     */
    protected function _redirectOldSearchUrls()
    {

        /* @var $block Mage_Core_Block_Abstract */
        /* @var Semknox_ProductSearch_Helper_Data $helper */
        $helper = Mage::helper('semknoxps');
        $query  = Mage::app()->getRequest()->getParams();


        // 1) if semknox is active and we are on a old search route: redirect to semknox
        if($helper->semknoxpsIsActive() && $this->_isMagentoSearchRoute())
        {
            // if parameter _swtw is set and a current timestamp (newer than 1 minute): skip redirect to semknox module
            if(isset($query['_swtw'])) {
                if(($query['_swtw'] > time() - 60)) {
                    return;
                }

                unset($query['_swtw']);
            }

            // slug for semknox product search route. e.g. "semknoxps"
            $goTo = $helper->getResultsUrl();


            // set parameters

            // advanced search
            if(!isset($query['q']) and isset($query['name']))
            {
                $query['q'] = $query['name'];
                unset($query['name']);
            }

        }


        // 2) if semknox is not active and we are on a semknox search route: redirect to magento
        else if(! $helper->semknoxpsIsActive() && $this->_isSemknoxSearchRoute()) {
            $goTo ='catalogsearch/result';
        }


        // do redirect
        if(isset($goTo))
        {
            $url = Mage::getUrl($goTo, array(
                '_query' => $query
            ));

            Mage::app()->getResponse()
                ->setRedirect( trim($url,'/') , 301)
                ->sendResponse();
        }
    }

    /**
     * Check if Magento search module
     * @return bool
     */
    protected function _isMagentoSearchRoute()
    {
        $moduleName = Mage::app()->getRequest()->getModuleName();
        $actionName = Mage::app()->getRequest()->getActionName();
        $controllerName = Mage::app()->getRequest()->getControllerName();


        $isIndexSearchOrTermSearch = ($moduleName == 'catalogsearch' && in_array($controllerName,
                array('index', 'result')));
        $isAdvancedSearchResults   = ($moduleName == 'catalogsearch' && $controllerName == 'advanced' && $actionName == 'result');

        // return true if we are on standard or advanced magento search
        return ($isIndexSearchOrTermSearch || $isAdvancedSearchResults);
    }

    /**
     * Check if Semknox search module
     * @return bool
     */
    protected function _isSemknoxSearchRoute()
    {
        $moduleName = Mage::app()->getRequest()->getModuleName();

        return  ($moduleName === 'semknoxps');
    }

















}