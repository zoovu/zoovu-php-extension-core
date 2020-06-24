<?php namespace Semknox\Core\Services;

use Semknox\Core\Services\Search\SearchResponse;
use Semknox\Core\SxConfig;

class SearchService {
    protected $client;

    protected $page = 1;

    protected $limit = 100;

    /**
     * Set filter values
     * @var array
     */
    protected $filters = [];

    public function __construct(ApiClient $client)
    {
        $this->client = $client;

        return $this;
    }


    /**
     * Set the search query.
     * @param $query
     * @return SearchService
     */
    public function query($query)
    {
        $this->client->setParam('query', $query);

        return $this;
    }

    /**
     * Add an additional filter
     * @param string $name
     * @param mixed $values
     * @return SearchService
     */
    public function addFilter($name, $values)
    {
        if(count($values) === 2 && count(array_filter($values, 'is_numeric')) === 2) {
            $this->filters[] = [
                'name' => $name,
                'min' => min($values),
                'max' => max($values)
            ];
        }
        else {
            $values = array_map(function($value) {
                return [
                    'name' => $value
                ];
            }, $values);

            $this->filters[] = [
                'name' => $name,
                'values' => $values
            ];
        }

        return $this;
    }

    /**
     * Sort the results by a given query.
     * @param string $sortName
     * @return SearchService
     */
    public function sortBy($sortName, $direction=null)
    {
        if($direction === null) {
            $this->client->setParam('sort', $sortName);
        }
        else {
            $sort = ['key' => $sortName, 'direction' => $direction];
            $this->client->setParam('sortComplex', json_encode($sort));
        }

        return $this;
    }

    /**
     * Set the current page of results
     * @param int $page
     * @return SearchService
     */
    public function setPage(int $page)
    {
        $this->page = $page;

        return $this;
    }

    /**
     * Set how many results to return
     * @param int $limit
     * @return SearchService
     */
    public function setLimit(int $limit)
    {
        $this->limit = $limit;

        return $this;
    }

    /**
     * Set the user group for this search.
     * @param $userGroup
     *
     * @return $this
     */
    public function setUserGroup($userGroup)
    {
        $this->client->setParam('userGroup', $userGroup);
        return $this;
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
     * Return the url that's being queried for the search.
     * @return string
     */
    public function getRequestUrl()
    {
        $this->setSearchParameters();

        return $this->client->getRequestUrl('search');
    }



    /**
     * Set additional parameters before submitting the search.
     *
     */
    private function setSearchParameters()
    {
        $this->client->setParam('offset', ($this->page - 1) * $this->limit);
        $this->client->setParam('limit', $this->limit);

        $this->client->setParam('filters', json_encode($this->filters));
    }
}