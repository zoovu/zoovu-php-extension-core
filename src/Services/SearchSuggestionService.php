<?php namespace Semknox\Core\Services;

use Semknox\Core\Services\Search\SearchResponse;
use Semknox\Core\Services\SearchSuggestions\SearchSuggestionResponse;
use Semknox\Core\SxConfig;

class SearchSuggestionService {
    /**
     * @var ApiClient
     */
    protected $client;

    /**
     * How many results
     * @var int[]
     */
    protected $limits = [
        'brand' => 2,
        'search' => 3,
        'category' => 3,
        'product' => 5,
        'content' => 3,
    ];

    public function __construct(ApiClient $client)
    {
        $this->client = $client;
    }

    /**
     * Set the search query.
     * @param $query
     * @return $this
     */
    public function query($query)
    {
        $this->client->setParam('query', $query);

        return $this;
    }

    /**
     * Limit the results for the search suggestion
     * @param string $what
     * @param int $limit
     * @return $this
     */
    private function limit(string $what, int $limit)
    {
        $this->client->setParam($what, $limit);

        return $this;
    }

    /**
     * Set how many brand-results to return.
     * @param int $limit
     * @return $this
     */
    public function limitBrand(int $limit)
    {
        return $this->limit('limitBrand', $limit);
    }

    /**
     * Set how many search-results to return.
     * @param int $limit
     * @return $this
     */
    public function limitSearch(int $limit)
    {
        return $this->limit('limitSearch', $limit);
    }

    /**
     * Set how many category-results to return.
     * @param int $limit
     * @return $this
     */
    public function limitCategory(int $limit)
    {
        return $this->limit('limitCategory', $limit);
    }

    /**
     * Set how many product-results to return.
     * @param int $limit
     * @return $this
     */
    public function limitProduct(int $limit)
    {
        return $this->limit('limitProduct', $limit);
    }

    /**
     * Set how many content-results to return.
     * @param int $limit
     * @return $this
     */
    public function limitContent(int $limit)
    {
        return $this->limit('limitContent', $limit);
    }

    /**
     * Set the
     * @param string $usergroup
     * @return $this
     */
    public function setUserGroup(string $usergroup)
    {
        $this->client->setParam('userGroup', $usergroup);

        return $this;
    }

    /**
     * Start the search.
     * @return SearchResponse
     */
    public function search()
    {
        $response = $this->client->request('get', 'search/suggestions');

        return new SearchSuggestionResponse($response);
    }
}