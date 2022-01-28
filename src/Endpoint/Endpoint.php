<?php

namespace Weble\RevisoApi\Endpoint;

use GuzzleHttp\Psr7\Uri;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Message\UriInterface;
use stdClass;
use Weble\RevisoApi\Client;
use Weble\RevisoApi\Collection;
use Weble\RevisoApi\EmptyModel;
use Weble\RevisoApi\Exceptions\ErrorResponseException;
use Weble\RevisoApi\Model;

class Endpoint
{
    public const TYPE_POST = 'post';
    public const TYPE_PUT = 'put';

    protected Client $client;
    protected UriInterface $uri;
    protected ?object $info = null;
    protected int $perPage = 20;
    protected int $page = 0;
    protected ListEndpoint $listEndpoint;

    public function __construct(Client $client, UriInterface $uri)
    {
        $this->client = $client;
        $this->uri = $uri;

        $this->listEndpoint = new ListEndpoint($this->client, $this->getListRoute(), $this->getResourceKey());
    }

    /**
     * @throws ErrorResponseException
     */
    public function get(): Collection
    {
        return $this->listEndpoint
            ->perPage($this->perPage)
            ->page($this->page)
            ->get();
    }

    public function where(string $filterName, string $operator, $value): static
    {
        $this->listEndpoint->where($filterName, $operator, $value);
        return $this;
    }

    /**
     * @throws ErrorResponseException
     * @throws ClientExceptionInterface
     */
    public function create(array $data = []): Model
    {
        return (new EmptyModel($this->client, $this->getCreateRoute(), $this->getResourceKey()))
            ->save($data);
    }

    /**
     * @throws ErrorResponseException
     */
    public function find(object|string $item): Model
    {
        if (is_object($item)) {
            $data = $this->fetchFromRoute(new Uri($item->self));
            return new Model($this->client, $this->getResourceKey(), $data);
        }

        $params = $this->getRouteParameters($this->getFindRoute());
        $key = $params->first();

        $data = $this->fetchFromRoute($this->getFindRoute(), [$key => $item]);
        return new Model($this->client, $this->getResourceKey(), $data);
    }

    /**
     * @throws ErrorResponseException
     * @throws ClientExceptionInterface
     */
    public function fetchFromRoute(UriInterface $uri, array $parameters = []): \stdClass|string
    {
        $params = $this->getRouteParameters($uri);

        foreach ($params as $parameter) {
            if (isset($parameters[$parameter])) {
                $params[$parameter] = $parameters[$parameter];
            }
        }

        $queryParams = array_diff($parameters, $params->toArray());
        $path = $uri->getPath();
        foreach ($params as $key => $value) {
            $path = str_ireplace(urlencode('{') . $key . urlencode('}'), $value, $path);
        }

        $uri = $uri->withPath($path);

        return $this->client->get($uri, $queryParams);
    }

    public function perPage(int $number): static
    {
        $this->perPage = $number;
        return $this;
    }

    public function page(int $number): static
    {
        $this->page = $number;
        return $this;
    }

    /**
     * @throws ErrorResponseException
     */
    public function getListRoute(): UriInterface
    {
        return Client::createUri($this->getRouteList()->first()?->path ?? '');
    }

    /**
     * @throws ErrorResponseException
     */
    public function getFindRoute(): UriInterface
    {
        return Client::createUri($this->getRouteList()->get(1)?->path ?? '');
    }

    /**
     * @throws ErrorResponseException
     */
    public function getCreateRoute(): UriInterface
    {
        return Client::createUri($this->getRouteList()->where('method', 'POST')->first()?->path ?? '');
    }

    /**
     * @throws ErrorResponseException
     */
    public function getDeleteRoute(): UriInterface
    {
        return Client::createUri($this->getRouteList()->where('method', 'DELETE')->first()?->path ?? '');
    }

    /**
     * @throws ErrorResponseException
     */
    public function getResourceKey(): string|int|null
    {
        return $this->getRouteParameters($this->getFindRoute())->first() ?? null;
    }

    /**
     * @throws ErrorResponseException
     */
    public function getName(): string
    {
        return $this->getInfo()->name ?? '';
    }

    /**
     * @throws ErrorResponseException
     */
    public function getPostSchema(): object
    {
        return $this->getSchema('post');
    }

    /**
     * @throws ErrorResponseException
     */
    public function getPutSchema(): object
    {
        return $this->getSchema('put');
    }

    /**
     * @throws ErrorResponseException
     * @throws ClientExceptionInterface
     */
    public function getSchema(string $type = self::TYPE_POST): object
    {
        $type = $type === self::TYPE_POST ? self::TYPE_POST : self::TYPE_PUT;

        $route = $this->getListRoute();
        $path = $route->getPath() . '/schema/' . $type;

        $route = $route->withPath($path);

        return $this->client->get($route);
    }

    /**
     * @throws ErrorResponseException
     * @throws ClientExceptionInterface
     */
    public function getInfo(): object
    {
        if ($this->info === null) {
            $this->info = $this->client->get($this->uri);
        }

        return $this->info;
    }

    /**
     * @throws ErrorResponseException
     * @throws ClientExceptionInterface
     */
    public function getRouteList(): \Illuminate\Support\Collection
    {
        return (new \Illuminate\Support\Collection($this->getInfo()->routes ?? new stdClass()))
            ->map(function (object $route) {
                $route->path = $this->cleanRouteParameters(Client::createUri($route->path ?? ''));
                return $route;
            });
    }

    /**
     * @throws ErrorResponseException
     * @throws ClientExceptionInterface
     */
    public function getRoutes(): \Illuminate\Support\Collection
    {
        return $this->getRouteList();
    }

    public function getRouteParameters(UriInterface $route): \Illuminate\Support\Collection
    {
        $route = $this->cleanRouteParameters($route);
        $matches = [];
        $regex = '/{(.*?)}/i';
        preg_match_all($regex, urldecode($route->getPath()), $matches, PREG_SET_ORDER);

        $parameters = [];
        foreach ($matches as $placeholder) {
            if ($placeholder && (is_countable($placeholder) ? count($placeholder) : 0) > 1) {
                $parameters[] = $placeholder[1];
            }
        }

        return new \Illuminate\Support\Collection($parameters);
    }

    protected function cleanRouteParameters(UriInterface $route): UriInterface
    {
        $path = urldecode($route->getPath());

        $matches = [];
        $regex = '/{(.*?)}/i';
        preg_match_all($regex, $path, $matches, PREG_SET_ORDER);

        foreach ($matches as $placeholder) {
            if ($placeholder && (is_countable($placeholder) ? count($placeholder) : 0) > 1) {
                $parts = explode(":", $placeholder[1]);
                $param = array_shift($parts);
                $path = str_ireplace($placeholder[1], $param, $path);
            }
        }

        return $route->withPath($path);
    }
}
