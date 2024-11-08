<?php namespace Semknox\Core\Services;

use Semknox\Core\Exceptions\SearchQueryTooShortException;
use Semknox\Core\Interfaces\SearchResponseInterface;
use Semknox\Core\Services\Search\SearchResponse;
use Semknox\Core\Services\Search\SearchResponseQueryTooShort;

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
     * Search products depending on the current category. Used for SEMKNOX navigational search. Takes the current category and all parent categories as parameter. The topmost category must be the first category in the array. [CATEGORY1, SUBCATEGORY1, SUBSUBCATEGORY1]
     * @see https://docs.semknox.com/#27fe19d7-e72c-4746-92a3-2b7bf7c8235f
     * @param array $categories
     */
    public function queryCategory(array $categories)
    {
        // query always starts with "_#" followed by your path to the leave category
        // e.g. "Electronics > Phones > Smartphones" would be _#Electronics#Phones#Smartphones
        $query = sprintf('_#%s', implode('#', $categories));

        return $this->query($query);
    }

    /**
     * Add a filter to the search. Use the name of the filter and the string-value to filter for.
     * For categories, you can pass the path in the form "ROOT/category1/subcategory1.1".
     *
     * @param string $name The name of the filter
     * @param mixed $values A single value or an array of values.
     * @return SearchService
     */
    public function addFilter($name, $values)
    {
        $isRangeFilter = is_array($values)
                         && count($values) === 2
                         && count(array_filter($values, 'is_numeric')) === 2;

        if($isRangeFilter) {
            $this->filters[] = [
                'name' => $name,
                'min' => (float) min($values),
                'max' => (float) max($values)
            ];
        }
        else {
            if(!is_array($values)) {
                $values = [$values];
            }

            // convert each value to an array with key: "name" // 2020-08-118: now its the key "value"
            $values = array_map(function($value) {
                return [
                    'value' => $value
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
     * @return SearchResponseInterface
     */
    public function search()
    {
        try {
            $this->setSearchParameters();

            $response = $this->client->request('get', 'search');

            // redirect?
            if(isset($response['redirect'])){
                header("Location: ". $response['redirect']);
                exit;
            }

            return new SearchResponse($response);
        }
        catch(SearchQueryTooShortException $e) {
            return new SearchResponseQueryTooShort();
        }
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

        // user sessionid, requirement from Feb 2021
        $this->client->setParam('sessionId', session_id());
    }
}