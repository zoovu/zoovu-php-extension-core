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
     * A list of active option objects. Each active option has the keys: key, name and value
     *
     * @var array
     */
    protected $activeOptions;

    /**
     * A list of
     * @var
     */
    protected $isActive;

    /**
     * Initialize an option.
     *
     * @param array $optionData
     * @param array|null $activeOptions
     */
    public function __construct(array $optionData, $activeOptions=[])
    {
        $this->optionData = $optionData;

        if(!isset($this->optionData['active'])) {
            $this->optionData['active'] = false;
        }

        if($activeOptions) {
            $this->activeOptions = $activeOptions;

            $activeOptionKeys = array_map(function($value) {
                return $value['value'];
            }, $activeOptions);
            
            if(in_array($this->getValue(), $activeOptionKeys)) {
                $this->setActive(true);
            }
        }
    }

    /**
     * Alias for $this->getId()
     * @return mixed.
     */
    public function getId()
    {
        return $this->getKey();
    }

    /**
     * Return the key for this filter
     * @return mixed.
     */
    public function getKey()
    {
        return isset($this->optionData['key'])
            ? $this->optionData['key']
            : $this->optionData['conceptId'];
    }

    /**
     * Return the value for this filter
     * @return mixed.
     */
    public function getValue()
    {
        return isset($this->optionData['value'])
        ? $this->optionData['value']
        : $this->optionData['name'];
    }

    /**
     * Return the unit for this filter
     * @return mixed.
     */
    public function getUnit()
    {
        return isset($this->optionData['unit'])
        ? $this->optionData['unit']
        : '';
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
     * Return the color of the filter option.
     * @return string
     */
    public function getColor()
    {
        return isset($this->optionData['color'])
            ? $this->optionData['color']
            : '';
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
            $result[] = new Option($child, $this->activeOptions);
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