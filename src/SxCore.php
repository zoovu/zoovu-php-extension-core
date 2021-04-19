<?php namespace Semknox\Core;

use Semknox\Core\Services\ApiClient;
use Semknox\Core\Services\InitialUploadOverviewService;
use Semknox\Core\Services\InitialUploadService;
use Semknox\Core\Services\ProductUpdateService;
use Semknox\Core\Services\SearchService;
use Semknox\Core\Services\SearchSuggestionService;

class SxCore {

    /**
     * @var SxConfig
     */
    private $config;

    public function __construct(SxConfig $config)
    {
        if(PHP_VERSION < '5.6') {
            throw new \Exception('PHP 5.6 or higher is required for this package');
        }

        $this->config = $config;
    }

    /**
     * Return the service to update one or a few (not all) products.
     *
     * @return ProductUpdateService
     */
    public function getProductUpdater()
    {
        $client = new ApiClient($this->config);

        return new ProductUpdateService($client, $this->config);
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
     * Return a service that gives a bird-eye-view of all the currently running initial uploads.
     */
    public function getInitialUploadOverview()
    {
        return new InitialUploadOverviewService($this->config);
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

    /**
     * Return the searchSuggestions service.
     * @return SearchSuggestionService
     */
    public function getSearchSuggestions()
    {
        $client = new ApiClient($this->config);

        return new SearchSuggestionService($client);
    }
}