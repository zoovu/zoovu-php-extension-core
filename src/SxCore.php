<?php namespace Semknox\Core;

use Semknox\Core\Services\ApiClient;
use Semknox\Core\Services\InitialUploadService;
use Semknox\Core\Services\SearchService;

class SxCore {

    /**
     * @var SxConfig
     */
    private $config;


    public function __construct(SxConfig $config)
    {
        if(PHP_VERSION < '5.5') {
            throw new \Exception('PHP 5.5 or higher is required for this package');
        }

        $this->config = $config;
    }

    /**
     * Return the service to process an initial product upload.
     *
     * @return InitialUploadService
     */
    public function getInitialUploader()
    {
        $client = new ApiClient($this->config);

        return new InitialUploadService($client, $this->config);
    }

    /**
     * Return the search service.
     *
     * @return SearchService
     */
    public function getSearch()
    {
        $client = new ApiClient($this->config);

        return new SearchService($client);
    }
}