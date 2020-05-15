<?php namespace Semknox\Core\Services\Traits;

use Semknox\Core\Services\ApiClient;
use Semknox\Core\SxConfig;

trait SingletonTrait
{
    /**
     * @var self
     */
    private static $instance;

    /**
     * Get
     * @param SxConfig $config
     *
     * @return self
     */
    public static function getInstance(ApiClient $client, SxConfig $config=null)
    {
        if(!self::$instance) {
            self::$instance = new self($client, $config);
        }

        return self::$instance;
    }
}