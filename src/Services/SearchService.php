<?php namespace Semknox\Core\Services;

use Semknox\Core\Services\Search\SearchResponse;
use Semknox\Core\SxConfig;

class SearchService {
    protected $client;

    protected $page = 1;

    protected $limit = 100;

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
     * Set the current page of results
     * @param int $page
     */
    public function setPage(int $page)
    {
        $this->page = $page;
    }

    /**
     * Set how many results to return
     */
    public function setLimit(int $limit)
    {
        $this->limit = $limit;
    }

    /**
     * Start the search.
     * @return SearchResponse
     */
    public function search()
    {
        $this->setSearchParameters();

        $response = $this->client->request('get', 'search');

        return new SearchResponse($response);
    }

    /**
     * Set additional parameters before submitting the search.
     *
     */
    private function setSearchParameters()
    {
        $this->client->setParam('offset', ($this->page - 1) * $this->limit);
        $this->client->setParam('limit', $this->limit);
    }
}