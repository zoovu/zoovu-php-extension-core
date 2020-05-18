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

    public function getProjectId()
    {
        return $this->get('projectId');
    }

    public function getTimeout()
    {
        return $this->get('requestTimeout', 15);
    }

    /**
     * Get the maximum batch size for the initial upload. This size defines how many products are collected in memory before they are permanented to a file. This also defines how many products are sent to semknox in one request.
     * @return int
     */
    public function getInitialUploadBatchSize()
    {
        return $this->get('initialUploadBatchSize');
    }

    /**
     * Identifier for initial upload. Useful for differentiating between different shops or different language versions of one shop. Defaults to "semknox-upload".
     * @return mixed|null
     */
    public function getInitialUploadDirectoryIdentifier()
    {
        return $this->get('initialUploadIdentifier', 'semknox-upload');
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