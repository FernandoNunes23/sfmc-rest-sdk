<?php


namespace SfmcRestSdk;


use League\OAuth2\Client\Provider\GenericProvider;
use Phpfastcache\Helper\Psr16Adapter;
use SfmcRestSdk\DataExtension\SyncCall;

/**
 * Class Client
 * @package SfmcRestSdk
 */
class Client
{
    private $clientId;
    private $clientSecret;
    private $urlAccessToken;
    private $subdomain;
    private $provider;
    private $cache;
    private $requestClient;

    /**
     * Client constructor.
     *
     * @param string $clientId
     * @param string $clientSecret
     * @param string $subdomain
     *
     * @throws \Phpfastcache\Exceptions\PhpfastcacheDriverCheckException
     * @throws \Phpfastcache\Exceptions\PhpfastcacheDriverException
     * @throws \Phpfastcache\Exceptions\PhpfastcacheDriverNotFoundException
     * @throws \Phpfastcache\Exceptions\PhpfastcacheInvalidArgumentException
     * @throws \Phpfastcache\Exceptions\PhpfastcacheInvalidConfigurationException
     * @throws \Phpfastcache\Exceptions\PhpfastcacheLogicException
     * @throws \ReflectionException
     */
    public function __construct(string $clientId, string $clientSecret, string $subdomain)
    {
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
        $this->urlAccessToken = "https://{$subdomain}.auth.marketingcloudapis.com/v2/token";
        $this->subdomain = $subdomain;

        $defaultDriver = 'Files';
        $this->cache = new Psr16Adapter($defaultDriver);

        $this->provider = new GenericProvider([
            'clientId'                => $this->clientId,    // The client ID assigned to you by the provider
            'clientSecret'            => $this->clientSecret,    // The client password assigned to you by the provider
            'redirectUri'             => 'example',
            'urlAuthorize'            => 'example',
            'urlAccessToken'          => $this->urlAccessToken,
            'urlResourceOwnerDetails' => 'example'
        ]);

        $this->requestClient = new \GuzzleHttp\Client([
            'base_uri' => "https://$subdomain.rest.marketingcloudapis.com/"
        ]);
    }

    /**
     * @return string
     */
    public function getSubdomain(): string
    {
        return $this->subdomain;
    }

    /**
     * @param string $uri
     * @param array|null $query
     * @param array|null $headers
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Phpfastcache\Exceptions\PhpfastcacheSimpleCacheException
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function get(string $uri, ?array $query = null, ?array $headers = null): \Psr\Http\Message\ResponseInterface
    {
        $params = [];

        $headers['Authorization'] = "Bearer {$this->getToken()}";

        if (!empty($headers)) {
            $params['headers'] = $headers;
        }

        if (!empty($query)) {
            $params['query'] = $query;
        }

        return $this->requestClient->request('GET', $uri, $params);
    }

    /**
     * @param string $uri
     * @param array|null $body
     * @param array|null $headers
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Phpfastcache\Exceptions\PhpfastcacheSimpleCacheException
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function post(string $uri, ?array $body = null, ?array $headers = null): \Psr\Http\Message\ResponseInterface
    {
        $params = [];

        $headers['Authorization'] = "Bearer {$this->getToken()}";

        if (!empty($headers)) {
            $params['headers'] = $headers;
        }

        if (!empty($body)) {
            $params['body'] = $body;
        }

        return $this->requestClient->request('POST', $uri, $params);
    }

    /**
     * @param string $uri
     * @param array|null $body
     * @param array|null $headers
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Phpfastcache\Exceptions\PhpfastcacheSimpleCacheException
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function postJson(string $uri, ?array $body = null, ?array $headers = null): \Psr\Http\Message\ResponseInterface
    {
        $params = [];

        $headers['Authorization'] = "Bearer {$this->getToken()}";

        if (!empty($headers)) {
            $params['headers'] = $headers;
        }

        if (!empty($body)) {
            $params['json'] = $body;
        }

        return $this->requestClient->request('POST', $uri, $params);
    }

    /**
     * @return \League\OAuth2\Client\Token\AccessToken|\League\OAuth2\Client\Token\AccessTokenInterface|mixed
     * @throws \Phpfastcache\Exceptions\PhpfastcacheSimpleCacheException
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function getToken()
    {
        try {
            $cachedToken = $this->getCachedTokenIfExists();

            if($cachedToken == false) {
                $accessToken = $this->provider->getAccessToken('client_credentials');
                $this->cache->set('access_token', serialize($accessToken));
            } else {
                $accessToken = $this->getRefreshToken($cachedToken);
            }
        } catch (\League\OAuth2\Client\Provider\Exception\IdentityProviderException $e) {
            // Failed to get the access token
            exit($e->getMessage());
        }

        return $accessToken;
    }

    /**
     * @return false|mixed
     * @throws \Phpfastcache\Exceptions\PhpfastcacheSimpleCacheException
     * @throws \Psr\Cache\InvalidArgumentException
     */
    private function getCachedTokenIfExists()
    {
        $token = $this->cache->get('access_token', false);
        
        return $token ? unserialize($token) : false;
    }

    /**
     * @param $cachedToken
     *
     * @return \League\OAuth2\Client\Token\AccessToken|\League\OAuth2\Client\Token\AccessTokenInterface|mixed
     * @throws \League\OAuth2\Client\Provider\Exception\IdentityProviderException
     * @throws \Phpfastcache\Exceptions\PhpfastcacheSimpleCacheException
     * @throws \Psr\Cache\InvalidArgumentException
     */
    private function getRefreshToken($cachedToken)
    {
        $accessToken = $cachedToken;

        if ($cachedToken->hasExpired()) {
            $accessToken = $this->provider->getAccessToken('refresh_token', [
                'refresh_token' => $cachedToken->getRefreshToken()
            ]);

            $this->cache->set('access_token', serialize($accessToken));
        }

        return $accessToken;
    }

    public function getSyncCall()
    {
        return new SyncCall($this);
    }
}