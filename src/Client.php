<?php

namespace Webleit\RevisoApi;

use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\RequestOptions;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;
use Webleit\RevisoApi\Exceptions\DetailedErrorResponseException;
use Webleit\RevisoApi\Exceptions\ErrorResponseException;
use Webleit\RevisoApi\Exceptions\GenericErrorResponseException;

/**
 * Class Client
 * @see https://github.com/opsway/zohobooks-api
 * @package Webleit\ZohoBooksApi
 */
class Client
{
    /**
     *
     */
    const ENDPOINT = 'https://rest.reviso.com/';

    /**
     * @var \GuzzleHttp\Client
     */
    protected $httpClient;

    /**
     * @var string
     */
    protected $appSecretToken;

    /**
     * @var string
     */
    protected $agreementGrantToken;

    /**
     * @var bool
     */
    protected $demo = false;

    /**
     * @var Client
     */
    protected static $instance;

    /**
     * Client constructor.
     * @param string $appSecretToken
     * @param string $agreementGrantToken
     */
    protected function __construct ($appSecretToken = 'demo', $agreementGrantToken = 'demo')
    {
        $this->appSecretToken = $appSecretToken;
        $this->agreementGrantToken = $agreementGrantToken;

        $this->createClient();
    }

    /**
     * @param Client $client
     * @return Client
     */
    public static function setInstance(Client $client)
    {
        self::$instance = $client;
        return self::$instance;
    }

    /**
     * @param string $appSecretToken
     * @param string $agreementGrantToken
     * @return Client
     */
    public static function getInstance ($appSecretToken = 'demo', $agreementGrantToken = 'demo')
    {
        if (!self::$instance) {
            self::$instance = new Client($appSecretToken, $agreementGrantToken);
            if ($appSecretToken == 'demo') {
                self::$instance->isDemo(true);
            }
        }

        return self::$instance;
    }

    /**
     * @param $url
     * @return bool
     */
    public static function isRevisoApiUrl ($url)
    {
        if (filter_var($url, FILTER_VALIDATE_URL) === false) {
            return false;
        }

        $url = new Uri($url);
        $restUri = new Uri(self::ENDPOINT);

        if ($url->getHost() == $restUri->getHost()) {
            return true;
        }

        return false;
    }

    /**
     * @return bool
     */
    public function isDemo ()
    {
        return $this->appSecretToken == 'demo' ? true : false;
    }

    /**
     * @return \GuzzleHttp\Client
     */
    protected function createClient ()
    {
        $this->httpClient = new \GuzzleHttp\Client([
            'base_uri' => $this->getEndPoint(),
            'http_errors' => false,
            'headers' => [
                'X-AppSecretToken' => $this->appSecretToken,
                'X-AgreementGrantToken' => $this->agreementGrantToken,
                'Content-Type' => 'application/json'
            ]
        ]);
        return $this->httpClient;
    }

    /**
     * @return UriInterface
     */
    public function getEndPoint ()
    {
        $uri = new Uri(self::ENDPOINT);

        if ($this->isDemo()) {
            $uri = Uri::withQueryValue($uri, 'demo', 'true');
        }

        return $uri;
    }

    /**
 * @param $url
 * @param array $params
 * @return \stdClass|string
 * @throws ErrorResponseException
 */
    public function get ($url, array $params = [])
    {
        $url = $this->getRequestUrl($url);

        foreach ($params as $key => $value) {
            $url = Uri::withQueryValue($url, $key, $value);
        }

        if ($this->isDemo()) {
            $url = Uri::withQueryValue($url, 'demo', 'true');
        }

        return $this->processResult(
            $this->httpClient->get($url)
        );
    }

    /**
     * @param $url
     * @param array $params
     * @return \stdClass|string
     * @throws ErrorResponseException
     */
    public function post ($url, array $params = [])
    {
        $url = $this->getRequestUrl($url);

        return $this->processResult(
            $this->httpClient->post($url, [
                RequestOptions::JSON => $params
            ])
        );
    }
    
    /**
     * @param $url
     * @param array $params
     * @return \stdClass|string
     * @throws ErrorResponseException
     */
    public function postFile($url, array $params = [])
    {
                
        $url = $this->getRequestUrl($url);

        return $this->processResult(
            $this->httpClient->post($url, [
                RequestOptions::MULTIPART => $params
            ])
        );
    }

    /**
     * @param $url
     * @param array $params
     * @return \stdClass|string
     * @throws ErrorResponseException
     */
    public function put ($url, array $params = [])
    {
        $url = $this->getRequestUrl($url);

        return $this->processResult(
            $this->httpClient->put($url, [
                RequestOptions::JSON => $params
            ])
        );
    }

    /**
     * @param $url
     * @return bool
     * @throws ErrorResponseException
     */
    public function delete ($url)
    {
        $url = $this->getRequestUrl($url);
        $response =  $this->httpClient->delete($url);

        if ($response->getStatusCode() >= 200 && $response->getStatusCode() <= 299) {
            return true;
        }

        throw new ErrorResponseException($response->getReasonPhrase(), $response->getStatusCode());
    }


    /**
     * @param ResponseInterface $response
     * @return bool|mixed|string
     * @throws DetailedErrorResponseException
     * @throws ErrorResponseException
     */
    protected function processResult (ResponseInterface $response)
    {
        try {
            $resultJson = json_decode($response->getBody());
        } catch (\InvalidArgumentException $e) {
            $resultJson = false;
        }

        // All ok, probably not json, like PDF?
        if ($response->getStatusCode() < 200 || $response->getStatusCode() > 299) {
            if (!$resultJson) {
                throw new ErrorResponseException('Internal API error: ' . $response->getStatusCode() . ' ' . $response->getReasonPhrase());
            }

            if (isset($resultJson->errorCode)) {
                throw new DetailedErrorResponseException($resultJson);
            }

            if (isset($resultJson->httpStatusCode)) {
                throw new GenericErrorResponseException($resultJson);
            }
        }

        if (!$resultJson) {
            // All ok, probably not json, like PDF?
            if ($response->getStatusCode() >= 200 && $response->getStatusCode() <= 299) {
                return (string) $response->getBody();
            }

           throw new ErrorResponseException('Internal API error: ' . $response->getStatusCode() . ' ' . $response->getReasonPhrase());
        }



        return $resultJson;
    }

    /**
     * @param $url
     * @return UriInterface|static
     */
    public function getRequestUrl ($url)
    {
        if ($url instanceof UriInterface) {
            $baseUri = $url;
            $path = $baseUri->getPath();
        } else {
            $baseUri = $this->getEndPoint();
            $path = $baseUri->getPath();
            $path .= $url;
        }

        $url = $baseUri->withPath($path);

        if ($this->isDemo()) {
            $url = Uri::withQueryValue($url, 'demo', 'true');
        }
        return $url;
    }
}