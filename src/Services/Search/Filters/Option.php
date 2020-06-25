<?php namespace Semknox\Core\Services\Search\Filters;

use Semknox\Core\Services\Traits\ArrayGetTrait;

/**
 * Represents a single option.
 * @package Semknox\Core\Services\Search\Filters
 */
class Option {
    use ArrayGetTrait;

    /**
     * @var array Result item data
     */
    protected $optionData;

    /**
     * Initialize an option.
     *
     * @param array $optionData
     */
    public function __construct(array $optionData)
    {
        $this->optionData = $optionData;
    }

    /**
     * Return the key for this filter
     * @return mixed.
     */
    public function getId()
    {
        return $this->optionData['conceptId'];
    }

    /**
     * Alias for $this->getId()
     * @return mixed.
     */
    public function getKey()
    {
        return $this->getId();
    }

    /**
     * Return the name of the concept.
     * @return string
     */
    public function getName()
    {
        return $this->optionData['name'];
    }

    /**
     * Return the name of the filter.
     * @return string
     */
    public function getViewName()
    {
        return $this->arrayGet($this->optionData, 'viewName', $this->getName());
    }

    public function getNumberOfResults()
    {
        return $this->arrayGet($this->optionData, 'count', 0);
    }

    /**
     * Get all available children-options
     * @return array
     */
    public function getChildren()
    {
        $children = $this->getChildrenFromApiResponse();
        $result = [];

        foreach($children as $child) {
            $result[] = new Option($child);
        }

        return $result;
    }

    /**
     * Get the "children" part of the api response.
     * @return array|mixed|null
     */
    private function getChildrenFromApiResponse()
    {
        return $this->arrayGet($this->optionData, 'children', []);
    }

    /**
     * Return if this option has child options.
     * @return bool
     */
    public function hasChildren()
    {
        return (bool) $this->getChildrenFromApiResponse();
    }

    /**
     * Return if this option is active. This property has been added by the Filter object (AbstractFilter).
     *
     * @param bool $active
     * @return Option
     */
    public function setActive($active=true)
    {
        $this->optionData['active'] = $active;

        return $this;
    }

    /**
     * Return if this option is active. This property has been added by the Filter object (AbstractFilter).
     */
    public function isActive()
    {
        return (bool) $this->arrayGet($this->optionData, 'active', false);
    }
}