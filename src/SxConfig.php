<?php namespace Semknox\Core;


use Semknox\Core\Exceptions\ConfigurationException;

class SxConfig {

    /**
     * @var array
     */
    private $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }


    private function get($key, $default=null)
    {
        return isset($this->config[$key])
            ? $this->config[$key]
            : $default;
    }

    /**
     * Return the configured Api url.
     * @return string
     */
    public function getApiUrl()
    {
        return $this->get('apiUrl', 'https://dev-api-v3.semknox.com/');
    }

    /**
     * Return the configured Api key.
     * @return string
     */
    public function getApiKey()
    {
        return $this->get('apiKey', '');
    }

    public function getStoreId()
    {
        return $this->get('storeId');
    }

    public function getTimeout()
    {
        return $this->get('requestTimeout', 15);
    }

    /**
     * Name of the product transformer class.
     * @return string
     */
    public function getProductTransformer()
    {
        return $this->get('productTransformer');
    }

    /**
     * Return the path to the storage directory.
     * @return string
     */
    public function getStoragePath()
    {
        $path = $this->get('storagePath');

        if(!$path) {
            throw new ConfigurationException('Configuration for `storagePath` is missing.');
        }
        else if(!is_string($path)) {
            throw new ConfigurationException('Configuration for `storagePath` has to be a string.');
        }

        return $path;
    }
}