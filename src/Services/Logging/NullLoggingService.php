<?php namespace Semknox\Core\Services\Logging;

use Semknox\Core\Interfaces\LoggingServiceInterface;

/**
 * Implementation of LoggingServiceInterface that does not write the information.
 *
 * @package Semknox\Core\Services\Logging
 */
class NullLoggingService implements LoggingServiceInterface {
    /**
     * @inheritDoc
     */
    public function info($message)
    {
        // does nothing

        return true;
    }

    /**
     * @inheritDoc
     */
    public function warning($message)
    {
        // does nothing

        return true;
    }

    /**
     * @inheritDoc
     */
    public function error($message)
    {
        // does nothing

        return true;
    }
}