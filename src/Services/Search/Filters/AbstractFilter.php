<?php namespace Semknox\Core\Services\Search\Filters;

abstract class AbstractFilter {
    /**
     * @var array Result item data
     */
    protected $filterData;

    /**
     * Initialize a filter object.
     *
     * @param array $filterData
     */
    public function __construct(array $filterData)
    {
        $this->filterData = $filterData;
    }

    /**
     * Alias for $this->getKey()
     * @return mixed.
     */
    public function getId()
    {
        return $this->filterData['key'];
    }

    /**
     * Return the key for this filter
     * @return mixed.
     */
    public function getKey()
    {
        return $this->filterData['key'];
    }

    /**
     * Return the name of the filter.
     * @return mixed.
     */
    public function getName()
    {
        return $this->filterData['name'];
    }

    /**
     * Set if this filter is active.
     * @param $isActive
     */
    public function setActive($isActive)
    {
        $this->filterData['active'] = (bool) $isActive;
    }

    /**
     * Return if the current filter is active.
     * @return bool
     */
    public function isActive()
    {
        return isset($this->filterData['active'])
            ? (bool) $this->filterData['active']
            : false;
    }

    /**
     * Get all available options for this filter.
     */
    public function getOptions()
    {
        $concepts = $this->filterData['categories'];
        $result = [];

        foreach($concepts as $concept) {
            $result[] = new Option($concept);
        }

        return $result;
    }
}