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
     * How many results to get for each content group.
     * @var int[]
     */
    protected $defaultLimits = [
        'brand' => 2,
        'search' => 3,
        'category' => 3,
        'product' => 1,
        'content' => 3,
    ];

    public function __construct(ApiClient $client)
    {
        $this->client = $client;

        foreach($this->defaultLimits as $name => $limit) {
            $this->limit($name, $limit);
        }
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
        $name = 'limit' . ucfirst($what);

        $this->client->setParam($name, $limit);

        return $this;
    }

    /**
     * Set how many brand-results to return.
     * @param int $limit
     * @return $this
     */
    public function limitBrand(int $limit)
    {
        return $this->limit('brand', $limit);
    }

    /**
     * Set how many search-results to return.
     * @param int $limit
     * @return $this
     */
    public function limitSearch(int $limit)
    {
        return $this->limit('search', $limit);
    }

    /**
     * Set how many category-results to return.
     * @param int $limit
     * @return $this
     */
    public function limitCategory(int $limit)
    {
        return $this->limit('category', $limit);
    }

    /**
     * Set how many product-results to return.
     * @param int $limit
     * @return $this
     */
    public function limitProduct(int $limit)
    {
        return $this->limit('product', $limit);
    }

    /**
     * Set how many content-results to return.
     * @param int $limit
     * @return $this
     */
    public function limitContent(int $limit)
    {
        return $this->limit('content', $limit);
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

    /**
     * Return the request uri for this search suggestion.
     * @return string
     */
    public function getRequestUrl()
    {
        return $this->client->getRequestUrl('search/suggestions');
    }
}