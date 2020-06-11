<?php namespace Semknox\Core\Services\Search\Sorting;

use Semknox\Core\Services\Traits\ArrayGetTrait;

class SortingOption {
    use ArrayGetTrait;

    protected $data;

    public function __construct($option)
    {
        $this->data = $option;
    }

    /**
     * Alias for getKey()
     * @return string
     */
    public function getId()
    {
        return $this->getKey();
    }

    /**
     * Return the name of the sorting option
     * @return string
     */
    public function getName()
    {
        return $this->arrayGet($this->data, 'name');
    }

    /**
     * The key (id) of the sorting option
     * @return string
     */
    public function getKey()
    {
        return $this->arrayGet($this->data, 'key');
    }

    /**
     * The key (id) of the sorting option
     * @return string
     */
    public function getType()
    {
        return $this->arrayGet($this->data, 'type');
    }

    /**
     * Return the sort direction: ASC or DESC
     * @return string
     */
    public function getSort()
    {
        return $this->arrayGet($this->data, 'sort');
    }
}