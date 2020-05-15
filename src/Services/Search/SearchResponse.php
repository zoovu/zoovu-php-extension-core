<?php

namespace Semknox\Core\Services\Search;

class SearchResponse
{
    /**
     * Raw Semknox search response
     * @var array
     */
    private $response;

    public function __construct(array $response)
    {
        $this->response = $response;
    }

    private function get($key, $default=null)
    {
        return isset($this->response[$key])
            ? $this->response[$key]
            : $default;
    }

    public function getInterpretedQuery()
    {
        return $this->get('interpretedQuery');
    }

    public function getTotalResults()
    {
        return $this->get('totalResults');
    }

    /**
     * Return the search results (i.e. the projects)
     */
    public function getResults()
    {
        return $this->get('searchResults');
    }
}