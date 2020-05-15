<?php namespace Semknox\Core\Services;

use Semknox\Core\Services\Search\SearchResponse;
use Semknox\Core\SxConfig;

class SearchService {
    protected $client;

    public function __construct(ApiClient $client)
    {
        $this->client = $client;
    }


    /**
     * Set the search query.
     * @param $query
     */
    public function query($query)
    {
        $this->client->setParam('query', $query);

        return $this;
    }

    /**
     * Start the search
     */
    public function search()
    {
        $response = $this->client->request('get', 'search');

        return new SearchResponse($response);
    }
}