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
}