<?php namespace Semknox\Core\Services;



use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Semknox\Core\SxConfig;

/**
 * Class Semknox_ProductSearch_Model_Api_APICommunicator
 * Provides methods to communicate with the Semknox API.
 */
class ApiClient
{
	const LOGINBASE = 'https://login.semknox.com/';

	const BASE = 'https://api-magento.semknox.com/';
	const BASE_DEV = 'https://stage-magento.semknox.com/';

	/**
	 * The maximum allowed value for _limit
	 */
	const MAXLIMIT = 108;


	protected $apiBaseUrl;

	/**
	 * Request parameters (GET parameters)
	 * @var
	 */
	protected $params;

	/**
	 * The client to be used
	 * @var Client
	 */
	protected $client;

	/**
	 * The content type to use
	 * @var string
	 */
	protected $_contentType = 'application/x-www-form-urlencoded';

    /**
     * The api key for the current request
     * @var string
     */
	protected $apiKey;

    /**
     * The project id for the current request
     * @var int
     */
	protected $projectId;


	public function __construct(SxConfig $config)
	{
		$this->client = new Client([
		    'base_uri' => $config->getApiUrl(),
            'timeout'  => $config->getTimeout(),
        ]);

		$this->setAuthentication(
		    $config->getProjectId(),
            $config->getApiKey()
        );
    }

	/**
	 * Set the query for the current request
	 */
	public function setQuery($query)
	{
		$this->setParam('query', $query);
	}

	/**
	 * Set a parameter for the current request
	 *
	 * @param $name
	 * @param $val
	 *
	 * @return self
	 */
	public function setParam($name, $val)
	{
		$this->params[$name] = $val;

		return $this;
	}

	/**
	 * Set the parameter "offset".
	 *
	 * @param $offset
	 *
	 * @return self
	 */
	public function setOffset($offset)
	{
		return $this->setParam('offset', $offset);
	}

	/**
	 * Set the parameter "limit".
	 *
	 * @param $limit
	 *
	 * @return self
	 */
	public function setLimit($limit)
	{
		return $this->setParam('limit', min($limit, self::MAXLIMIT));
	}

	/**
	 * Set the parameter "userGroup".
	 *
	 * @param $userGroup
	 *
	 * @return self
	 */
	public function setUserGroup($userGroup)
	{
		return $this->setParam('userGroup', $userGroup);
	}

	/**
	 * Send the request.
	 *
	 * @param $method
	 * @param $uri
	 *
	 * @return array
	 * @throws GuzzleException
	 * @throws \RuntimeException
	 */
	public function request($method, $uri)
	{
	    $uri = $this->replaceParametersInUri($uri);

        $requestParams = $this->makeGuzzleRequestParams($method);

//        var_dump('todo: '); var_dump($method); var_dump($uri); var_dump($requestParams); echo '<br>';
        $response = $this->client->request($method, $uri, $requestParams);
//var_dump('done: '); var_dump($method); var_dump($uri); var_dump($requestParams); echo '<br><br>';

		$content = $response->getBody()->getContents();

		// clear params
        $this->params = [];

		return json_decode($content, true);


		// TODO: check if any of that is still needed
		if(in_array(strtolower($method), array('get', 'delete'))) {
			$this->client->setParameterGet($this->params);
		}
		else {
			$body = array();
			foreach ( $this->params as $name => $value ) {
				if(is_array($value)) {
					$value = urlencode(json_encode($value));
				}

				$body[] = $name . '=' . $value;
			}

			$this->client->setHeaders('Content-Type', $this->_contentType);
			$this->client->setRawData(join('&', $body));
		}


		if($logRequest)
		{
			/* @var $helper Semknox_ProductSearch_Helper_Data */
			$helper = Mage::helper('semknoxps');
			$helper->log($this->client->getLastRequest());

		}

		return $request;
	}


    /**
     * Send a request asynchronously.
     * @param $method
     * @param $uri
     * @param $params
     *
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function requestAsync($method, $uri, $params)
	{
        if($params) {
            foreach($params as $key => $value) {
                $this->setParam($key, $value);
            }
        }

        $uri = $this->replaceParametersInUri($uri);

        $requestParams = $this->makeGuzzleRequestParams($method);

        return $this->client->requestAsync($method, $uri, $requestParams);
	}

	/**
	 * Return the uri with Login parameters for the users BackOffice Login.
	 * @param $uri
	 *
	 * @return string
	 */
	public function getBackOfficeLoginUri()
	{
		if( ! $this->isAuthenticationSet()) {
			$this->getApiCredentials();
		}

		$uri   = self::LOGINBASE;
		$query = http_build_query($this->params);

		if($query) {
			$uri .= '?' . $query;
		}

		return $uri;
	}

	/**
	 * Return the uri with all parameters for the current request.
	 * @param $uri
	 * @param $addCredentials
	 *
	 * @return string
	 */
	public function getRequestUri($uri, $addCredentials = true)
	{

		if( $addCredentials && ! $this->isAuthenticationSet()) {
			$this->getApiCredentials();
		}

		$uri   = $this->apiBaseUrl . $uri;
		$query = http_build_query($this->params);

		if($query) {
			$uri .= '?' . $query;
		}

		return $uri;
	}

    public function getAuthentication()
    {
        $queryParams = [];
        
        if($this->apiKey) $queryParams['apiKey'] = $this->apiKey;
        if($this->projectId) $queryParams['projectId'] = $this->projectId;

        return $queryParams;
	}

	/**
	 * Set user authentification
	 *
	 * @param int $projectId
	 * @param string $apiKey
	 *
	 * @return $this
	 */
	public function setAuthentication($projectId, $apiKey)
	{
		$this->projectId = $projectId;
		$this->apiKey = $apiKey;

		return $this;
	}

	/**
	 * Returns true if the authentication parameters are set, otherwise false.
	 *
	 * @return bool
	 */
	protected function isAuthenticationSet()
	{
		return isset(
			$this->projectId,
			$this->apiKey
		);
	}

    /**
     * Replace :param with a value in the url.
     * For example replaces :customerId by real customerId in url.
     *
     * @param $uri
     */
    private function replaceParametersInUri($uri)
    {
        $uri = str_replace(':apiKey', $this->apiKey, $uri);
        $uri = str_replace(':projectId', $this->projectId, $uri);

        foreach($this->params as $key => $value) {
            if(strpos($uri, ':' . $key) !== false) {
                $uri = str_replace(":$key", $value, $uri);
            }
        }

        return $uri;
    }

    /**
     * Prepare the guzzle options parameter
     * @param $method
     *
     * @return array
     */
    private function makeGuzzleRequestParams($method)
    {
        // apikey and projectId always url parameter
        $guzzleParams = [
            'query' => $this->getAuthentication(),
//            'debug' => true
        ];

        if(in_array(strtolower($method), array('get', 'delete'))) {
            $guzzleParams['query'] = array_merge($this->params, $guzzleParams['query']);
        }
        else {
            $guzzleParams['json'] = $this->params;
        }

        return $guzzleParams;
    }
}