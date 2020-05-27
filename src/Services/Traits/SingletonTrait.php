<?php namespace Semknox\Core\Services\Traits;

trait SingletonTrait
{
    /**
     * @var self
     */
    private static $instance;

    /**
     * Get an instance of this class object.
     *
     * @return self
     */
    public static function getInstance()
    {
        if(!self::$instance) {
            self::$instance = new self(...func_get_args());
        }

        return self::$instance;
    }
}