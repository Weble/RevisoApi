<?php

namespace Weble\RevisoApi;

use Http\Client\HttpClient;
use Http\Discovery\HttpClientDiscovery;
use Http\Discovery\Psr17FactoryDiscovery;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriFactoryInterface;
use Psr\Http\Message\UriInterface;
use RectorPrefix20220126\Symfony\Contracts\HttpClient\HttpClientInterface;
use Weble\RevisoApi\Exceptions\DetailedErrorResponseException;
use Weble\RevisoApi\Exceptions\ErrorResponseException;
use Weble\RevisoApi\Exceptions\GenericErrorResponseException;

class Client
{
    public const ENDPOINT = 'https://rest.reviso.com/';
    public const DEMO_TOKEN = 'demo';

    protected HttpClient $httpClient;
    protected string $appSecretToken;
    protected string $agreementGrantToken;

    protected static UriFactoryInterface $uriFactory;
    protected static RequestFactoryInterface $requestFactory;
    protected static StreamFactoryInterface $bodyFactory;

    public function __construct(string $appSecretToken = 'demo', string $agreementGrantToken = 'demo', ?HttpClientInterface $httpClient = null)
    {
        $this->appSecretToken = $appSecretToken;
        $this->agreementGrantToken = $agreementGrantToken;

        $this->httpClient = $httpClient ?? HttpClientDiscovery::find();
        static::$uriFactory = Psr17FactoryDiscovery::findUriFactory();
        static::$requestFactory = Psr17FactoryDiscovery::findRequestFactory();
        static::$bodyFactory = Psr17FactoryDiscovery::findStreamFactory();
    }

    public static function createUri(string $uri = ''): UriInterface
    {
        return static::$uriFactory->createUri($uri);
    }

    public static function createRequest(string $method, UriInterface $uri): RequestInterface
    {
        return static::$requestFactory
            ->createRequest($method, $uri);
    }

    public static function createBody(string $content = ''): StreamInterface
    {
        return static::$bodyFactory
            ->createStream($content);
    }

    public static function isRevisoApiUrl(string $url): bool
    {
        if (filter_var($url, FILTER_VALIDATE_URL) === false) {
            return false;
        }

        $url = static::createUri($url);
        $restUri =  static::createUri(static::ENDPOINT);

        return $url->getHost() === $restUri->getHost();
    }

    public function isDemo(): bool
    {
        return $this->appSecretToken === static::DEMO_TOKEN;
    }

    public function getEndPoint(): UriInterface
    {
        $uri = static::createUri(static::ENDPOINT);

        if ($this->isDemo()) {
            $uri = static::appendDemoParameter($uri);
        }

        return $uri;
    }


    /**
     * @throws ClientExceptionInterface
     */
    public function call(string $method, UriInterface $uri, array $data = [], array $headers = []): ResponseInterface
    {
        $request = $this->addHeadersToRequest(
            static::createRequest($method, $uri),
            $headers
        )->withBody(
            static::createBody(json_encode($data))
        );

        return $this->httpClient->sendRequest($request);
    }

    /**
     * @throws ErrorResponseException
     * @throws ClientExceptionInterface
     */
    public function get(string|UriInterface $url, array $params = []): object|bool|array|string
    {
        $url = $this->getRequestUrl($url);
        $url = static::appendParameters($url, $params);

        return $this->processResult(
            $this->call('GET', $url)
        );
    }

    /**
     * @throws ErrorResponseException
     * @throws ClientExceptionInterface
     */
    public function post(string|UriInterface $url, array $data = []): object|bool|array|string
    {
        $url = $this->getRequestUrl($url);

        return $this->processResult(
            $this->call('POST', $url, $data)
        );
    }

    /**
     * @throws ErrorResponseException
     * @throws ClientExceptionInterface
     */
    public function put($url, array $data = []): object|bool|array|string
    {
        $url = $this->getRequestUrl($url);

        return $this->processResult(
            $this->call('PUT', $url, $data)
        );
    }

    /**
     * @throws ErrorResponseException
     * @throws ClientExceptionInterface
     */
    public function delete(string|UriInterface $url): void
    {
        $url = $this->getRequestUrl($url);
        $response = $this->call("DELETE", $url);

        if ($response->getStatusCode() >= 200 && $response->getStatusCode() <= 299) {
            return;
        }

        throw new ErrorResponseException($response->getReasonPhrase(), $response->getStatusCode());
    }


    /**
     * @throws DetailedErrorResponseException
     * @throws ErrorResponseException
     */
    protected function processResult(ResponseInterface $response): bool|string|array|object
    {
        try {
            $resultJson = json_decode($response->getBody(), null, 512, JSON_THROW_ON_ERROR);
        } catch (\InvalidArgumentException|\JsonException) {
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
                return (string)$response->getBody();
            }

            throw new ErrorResponseException('Internal API error: ' . $response->getStatusCode() . ' ' . $response->getReasonPhrase());
        }


        return $resultJson;
    }

    public function getRequestUrl(string|UriInterface $url): UriInterface
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
            $url = static::appendDemoParameter($url);
        }

        return $url;
    }

    public static function appendParameters(UriInterface $uri, array $params): UriInterface
    {
        parse_str($uri->getQuery(), $query);
        return $uri->withQuery(http_build_query($query + $params));
    }

    protected static function appendDemoParameter(UriInterface $uri): UriInterface
    {
        return static::appendParameters($uri, [
            'demo' => 'true'
        ]);
    }

    protected function addHeadersToRequest(RequestInterface $request, $headers): RequestInterface
    {
        $headers = $headers + [
                'X-AppSecretToken'      => $this->appSecretToken,
                'X-AgreementGrantToken' => $this->agreementGrantToken,
                'Content-Type'          => 'application/json'
            ];

        foreach ($headers as $header => $value) {
            $request = $request->withHeader($header, $value);
        }

        return $request;
    }
}
