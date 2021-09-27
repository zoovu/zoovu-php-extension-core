<?php

/**
 * Interface for all the different filter implementations
 * @author aselle
 * @created 2021-09-16
 */

namespace Semknox\Core\Interfaces;



interface FilterInterface {
    /**
     * Return the type of this filter
     * @return string
     */
    public function getType();

    /**
     * Return the available options for this filter
     * @return Option[]
     */
    public function getOptions();

    /**
     * Return the key for the filter. This is an alias for getKey()
     *
     * @return int
     */
    public function getId();

    /**
     * Return the key of the filter.
     * @return int
     */
    public function getKey();

    /**
     * Return the name of the filter.
     * @return string
     */
    public function getName();

    /**
     * Return if this filter is currently active.
     * @return bool
     */
    public function isActive();
}