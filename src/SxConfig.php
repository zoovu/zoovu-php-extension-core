<?php
namespace Semknox\Core;


use Semknox\Core\Exceptions\ConfigurationException;

class SxConfig
{

    /**
     * Define
     * @var array
     */
    private $config = [
        // default config is defined here
        'apiKey'               => '',
        'projectId'            => '',
        'apiUrl'               => 'https://dev-api-v3.semknox.com/',

        // update date is stored on the file system and then sent to Semknox bundled
        // this config tells the core where to store the update data
        // it is required if you plan to do an initial upload
        'storagePath'          => '',

        // when this configuration is given, an instance of this class will
        // automatically try to convert the given product to a Semknox compatible format
        // For more information check the section `Product transformer`
        'productTransformer'   => null,

        // how many products to collect in one file / send in one request
        'uploadBatchSize'      => 2000,

        // how the directory to collect the products should be called
        'storeIdentifier'      => 'default',

        // how long (in seconds) a request should take before it gets aborted
        'requestTimeout'       => 15,

        // deletes all completed initial uploads except for the last X ones
        'keepCompletedUploads' => 5,

        // deletes all aborted initial uploads except for the last X ones
        'keepAbortedUploads'   => 1
    ];

    public function __construct(array $config)
    {
        $this->config = array_merge($this->config, $config);
    }

    /**
     * Get a value from the configuration.
     *
     * @param $key
     * @param null $default
     *
     * @return mixed|null
     */
    public function get($key, $default = null)
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
    public function merge(array $data, array $whitelist = [])
    {
        if ($whitelist) {
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
        return $this->get('apiUrl');
    }

    /**
     * Return the configured Api key.
     * @return string
     */
    public function getApiKey()
    {
        return $this->get('apiKey');
    }

    public function getProjectId()
    {
        return $this->get('projectId');
    }

    public function getTimeout()
    {
        return $this->get('requestTimeout');
    }

    /**
     * Get the maximum batch size for the products updates or the initial upload. This size defines how many products are collected in memory before they are permanented to a file. This also defines how many products are sent to semknox in one request.
     * @return int
     */
    public function getUploadBatchSize()
    {
        return $this->get('uploadBatchSize');
    }

    /**
     * Get the identifier for the current store. This can be e.g. "de" for a German language shop version.
     */
    public function getStoreIdentifier()
    {
        return $this->get('storeIdentifier');
    }

    /**
     * Identifier for initial upload. Useful for differentiating between different shops or different language versions of one shop. Returns "<projectId>-<storeIdentifier>-initialupload".
     * @return mixed|null
     */
    public function getInitialUploadDirectoryIdentifier()
    {
        return sprintf(
            '%s-%s-initialupload',
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
        return sprintf(
            '%s-%s-productupload',
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

        if (!$path) {
            throw new ConfigurationException('Configuration for `storagePath` is missing.');
        } elseif (!is_string($path)) {
            throw new ConfigurationException('Configuration for `storagePath` has to be a string.');
        }

        return $path;
    }

    /**
     * Return the number of previously completed uploads to keep. Older upload data will be deleted automatically.
     * @return int
     */
    public function getKeepLastCompletedUploads()
    {
        return $this->get('keepCompletedUploads');
    }

    /**
     * Return the number of aborted uploads to keep. Older uploads will be deleted automatically.
     * @return mixed|null
     */
    public function getKeepLastAbortedUploads()
    {
        return $this->get('keepAbortedUploads');
    }
}