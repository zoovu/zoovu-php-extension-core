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

    /**
     * Get a value from the configuration.
     *
     * @param $key
     * @param null $default
     *
     * @return mixed|null
     */
    public function get($key, $default=null)
    {
        return isset($this->config[$key])
            ? $this->config[$key]
            : $default;
    }

    /**
     * Set a configuration value.
     *
     * @param string $key
     * @param $value
     */
    public function set(string $key, $value)
    {
       $this->config[$key] = $value;
    }

    /**
     * Merge configuration data with the current config.
     *
     * @param array $data The data to merge
     * @param array $whitelist An array of allowed $data keys.
     */
    public function merge(array $data, array $whitelist=[])
    {
        if($whitelist) {
            $data = array_intersect_key($data, array_flip($whitelist));
        }

        $this->config = array_merge($this->config, $data);
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
     * Get the maximum batch size for the products updates or the initial upload. This size defines how many products are collected in memory before they are permanented to a file. This also defines how many products are sent to semknox in one request.
     * @return int
     */
    public function getUploadBatchSize()
    {
        return $this->get('uploadBatchSize', 2000);
    }

    /**
     * Get the identifier for the current store. This can be e.g. "de" for a German language shop version.
     */
    public function getStoreIdentifier()
    {
        return $this->get('storeIdentifier', 'default');
    }

    /**
     * Identifier for initial upload. Useful for differentiating between different shops or different language versions of one shop. Returns "<projectId>-<storeIdentifier>-initialupload".
     * @return mixed|null
     */
    public function getInitialUploadDirectoryIdentifier()
    {
        return sprintf('%s-%s-initialupload',
            $this->getProjectId(),
            $this->getStoreIdentifier()
        );
    }

    /**
     * Identifier for product update. Useful for differentiating between different shops or different language versions of one shop. Returns "<projectId>-<storeIdentifier>-productupdate".
     * @return mixed|null
     */
    public function getProductUpdateDirectoryIdentifier()
    {
        return sprintf('%s-%s-productupload',
            $this->getProjectId(),
            $this->getStoreIdentifier()
        );
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