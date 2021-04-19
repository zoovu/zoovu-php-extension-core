<?php
/**
 * File created for semknox-core.
 * @author aselle
 * @created 2021-04-19
 */

namespace Semknox\Core\Interfaces;

use Semknox\Core\Services\Search\Filters\TreeFilter;
use Semknox\Core\Services\Search\Product;
use Semknox\Core\Services\Search\Sorting\SortingOption;

interface LoggingServiceInterface
{
    /**
     * Log an informational message
     * @param string $message
     *
     * @return bool
     */
    public function info($message);

    /**
     * Log a warning message
     * @param string $message
     *
     * @return bool
     */
    public function warning($message);

    /**
     * Log an error message
     * @param string $message
     *
     * @return bool
     */
    public function error($message);
}