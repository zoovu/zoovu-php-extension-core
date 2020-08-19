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
     * Return the type of this filter.
     */
    abstract public function getType();

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
     * Return the unit for this filter
     * @return mixed.
     */
    public function getUnit()
    {
        return isset($this->filterData['unit'])
        ? $this->filterData['unit']
        : '';
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
     * Return the value of the filter.
     * @return mixed.
     */
    public function getValue()
    {
        return $this->filterData['value'];
    }

    /**
     * Set if this filter is active.
     * @param bool $isActive
     */
    public function setActive($isActive)
    {
        $this->filterData['active'] = $isActive;
    }

    /**
     * Set data for active options
     * @param array $activeOptions
     */
    public function setActiveOptions($activeOptions)
    {
        $this->filterData['activeOptions'] = $activeOptions;
    }

    /**
     * Return data for active options
     * @return array
     */
    public function getActiveOptions()
    {
        return isset($this->filterData['activeOptions'])
            ? $this->filterData['activeOptions']
            : [];
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
            $result[] = new Option($concept, $this->getActiveOptions());
        }

        return $result;
    }
}