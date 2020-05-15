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
	 * @var Varien_Http_Client
	 */
	protected $client;

	/**
	 * The content type to use
	 * @var string
	 */
	protected $_contentType = 'application/x-www-form-urlencoded';

	protected $storeId;


	public function __construct(SxConfig $config)
	{
		$this->storeId = $config->getStoreId();

		$this->client = new Client([
		    'base_uri' => $config->getApiUrl(),
            'timeout'  => $config->getTimeout()
        ]);

		$this->setAuthentication($config->getStoreId(), $config->getApiKey());
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
		if(is_array($val)) {
			$val = json_encode($val);
		}

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
	public function request($method, $uri, $logRequest = false)
	{
        $uri = $this->replaceParametersInUri($uri);

        $requestParams = $this->makeGuzzleRequestParams($method);

        $response = $this->client->request($method, $uri, $requestParams);

		$content = $response->getBody()->getContents();

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

	/**
	 * Set user authentification
	 *
	 * @param int $storeId
	 * @param string $apiKey
	 *
	 * @return $this
	 */
	public function setAuthentication($storeId, $apiKey)
	{
		$this->setParam('storeId', $storeId);
		$this->setParam('apiKey', $apiKey);

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
			$this->params['storeId'],
			$this->params['apiKey']
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
        $params = ['customerId', 'storeId'];

        foreach($params as $param) {
            if(strpos($uri, ':' . $param) !== false) {
                $uri = str_replace(":$param", $this->params[$param], $uri);
                unset($this->params[$param]);
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
        $params = [];

        if(in_array(strtolower($method), array('get', 'delete'))) {
            $params['query'] = $this->params;
        }
        else {
            $params['json'] = $this->params;
        }

        return $params;
    }
}