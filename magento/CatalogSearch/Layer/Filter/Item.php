<?php

/**
 * Layered Navigation block for search
 *
 */
class Semknox_ProductSearch_Model_CatalogSearch_Layer_Filter_Item 
{

    protected $item;
    protected $filterId;

    public function __construct($args)
    {

        $this->item = $args['item'];
        $this->filterId = $args['filterId'];

    }

    public function getCount()
    {
        return $this->item['count'];
    }

    public function getId()
    {
        return $this->item['id'];
    }


    public function getRemoveUrl()
    {
        $query = Mage::app()->getRequest()->getParams();

        $query = $this->removeFilterOptionFormUrlQuery($query);

        return $this->builtUrl($query);
    }

    public function getUrl()
    {
        $query = Mage::app()->getRequest()->getParams();

        if(isset($query['filter'][$this->filterId]) && is_array($query['filter'][$this->filterId]) && in_array($this->getId(), $query['filter'][$this->filterId]))
        {
            $query = $this->removeFilterOptionFormUrlQuery($query);

        } else {
            // generate filter set url
            $query['filter'][$this->filterId][] = $this->getId();
        }

        return $this->builtUrl($query);
    }

    public function getItemIsActive()
    {
        return isset($this->item['set']);
    }

    public function isSelected()
    {
        return $this->getItemIsActive();
    }

    public function getIconSrc()
    {
        if(isset($this->item['icon']) && strlen($this->item['icon']))
            return $this->item['icon'];

        return false;
    }

    public function getLabel()
    {
        $class = 'filter_'.$this->filterId;
        $class .= ' option_'.$this->getId();
        $class .= $this->getItemIsActive() ? ' active' : '';

        $dataHover = ($this->getIconSrc()) ? ' data-hover="'.$this->getIconSrc().'" ' : '';

        $dataValue = ' data-sxvalue="'.$this->filterId.'" ';
        $dataOption = ' data-sxoption="'.$this->getId().'" ';

        return '<span class="'.$class.'" '.$dataHover.$dataValue.$dataOption.' data-sxkey="filter">'.$this->item['viewName'].'</span>';
    }

    

    private function removeFilterOptionFormUrlQuery($query){

        // generate filter-unset url 
        $query['filter'][$this->filterId] = array_diff($query['filter'][$this->filterId], array($this->getId()));

        // reindex... to make url-parameters valid again
        $newIndexed = array();
        foreach($query['filter'][$this->filterId] as $filter){
            $newIndexed[] = $filter;
        }
        $query['filter'][$this->filterId] = $newIndexed;

        return $query;

    }

    private function builtUrl($query)
    {
         /* @var Semknox_ProductSearch_Helper_Data $helper */
        $helper = Mage::helper('semknoxps');

        if($helper->semknoxSeoUrlIsActive()) {

            $slug = $helper->getLastDirectoryInUrl();

        } else {

            $slug = $helper->getResultsUrl();
        }

        $url = Mage::getUrl($slug, array(
            '_query' => $query
        ));

        return $url;
    }


}
