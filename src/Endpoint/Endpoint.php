<?php

namespace Webleit\RevisoApi\Endpoint;

use Webleit\RevisoApi\Client;
use Webleit\RevisoApi\Collection;
use Webleit\RevisoApi\EmptyModel;
use Webleit\RevisoApi\Exceptions\ErrorResponseException;
use GuzzleHttp\Psr7\Uri;
use Psr\Http\Message\UriInterface;
use Webleit\RevisoApi\Model;

/**
 * Class Reviso
 * @package Webleit\RevisoApi
 */
class Endpoint
{
    /**
     * @var Client
     */
    protected $client;

    /**
     * @var UriInterface
     */
    protected $uri;

    /**
     * @var \stdClass
     */
    protected $info;


    /**
     * @var int
     */
    protected $perPage = 20;

    /**
     * @var int
     */
    protected $page = 0;
    /**
     * @var ListEndpoint
     */
    protected $listEndpoint;

    /**
     * Endpoint constructor.
     * @param Client $client
     * @param UriInterface $uri
     */
    public function __construct (Client $client, UriInterface $uri)
    {
        $this->client = $client;
        $this->uri = $uri;

        $this->listEndpoint = new ListEndpoint($this->client, $this->getListRoute(), $this->getResourceKey());
    }

    /**
     * @return Collection
     * @throws ErrorResponseException
     */
    public function get (): Collection
    {
        return $this->listEndpoint
            ->perPage($this->perPage)
            ->page($this->page)
            ->get();
    }

    /**
     * @param string $filterName
     * @param string $operator
     * @param $value
     * @return $this
     */
    public function where(string $filterName, string $operator, $value): self
    {
        $this->listEndpoint->where($filterName, $operator, $value);
        return $this;
    }

    /**
     * @param array $data
     * @return Model
     * @throws ErrorResponseException
     */
    public function create($data = [])
    {
        $newItem = new EmptyModel($this->getCreateRoute(), $this->getResourceKey());
        return $newItem->save($data);
    }

    /**
     * @param $item
     * @return Model
     * @throws ErrorResponseException
     */
    public function find ($item)
    {
        if (is_object($item)) {
            $data = $this->fetchFromRoute(new Uri($item->self));
            return new Model($data, $this->getResourceKey());
        }

        $params = $this->getRouteParameters($this->getFindRoute());
        $key = $params->first();

        $data = $this->fetchFromRoute($this->getFindRoute(), [$key => $item]);
        return new Model($data, $this->getResourceKey());
    }

    /**
     * @param UriInterface $uri
     * @param array $parameters
     * @return \stdClass|string
     * @throws ErrorResponseException
     */
    public function fetchFromRoute(UriInterface $uri, $parameters = [])
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

    /**
     * @param $number
     * @return $this
     */
    public function perPage ($number)
    {
        $this->perPage = $number;
        return $this;
    }

    /**
     * @param $number
     * @return $this
     */
    public function page ($number)
    {
        $this->page = $number;
        return $this;
    }

    /**
     * @return Uri
     * @throws ErrorResponseException
     */
    public function getListRoute ()
    {
        return new Uri($this->getRouteList()->first()->path);
    }

    /**
     * @return UriInterface
     * @throws ErrorResponseException
     */
    public function getFindRoute ()
    {
        return new Uri($this->getRouteList()->get(1)->path);
    }

    /**
     * @return UriInterface
     * @throws ErrorResponseException
     */
    public function getCreateRoute ()
    {
        return new Uri($this->getRouteList()->where('method', 'POST')->first()->path);
    }

    /**
     * @return UriInterface
     * @throws ErrorResponseException
     */
    public function getDeleteRoute ()
    {
        return new Uri($this->getRouteList()->where('method', 'DELETE')->first()->path);
    }

    /**
     * @return mixed
     * @throws ErrorResponseException
     */
    public function getResourceKey()
    {
        return $this->getRouteParameters($this->getFindRoute())->first();
    }

    /**
     * @return string
     * @throws ErrorResponseException
     */
    public function getName ()
    {
        return $this->getInfo()->name;
    }

    /**
     * @return object
     * @throws ErrorResponseException
     */
    public function getPostSchema ()
    {
        return $this->getSchema('post');
    }

    /**
     * @return object
     * @throws ErrorResponseException
     */
    public function getPutSchema ()
    {
        return $this->getSchema('put');
    }

    /**
     * @param string $type
     * @return \stdClass|string
     * @throws ErrorResponseException
     */
    public function getSchema ($type = 'post')
    {
        $type = $type == 'post' ? 'post' : 'put';

        $route = $this->getListRoute();
        $path = $route->getPath() . '/schema/' . $type;

        $route = $route->withPath($path);

        return  $this->client->get($route);
    }

    /**
     * @return \stdClass
     * @throws ErrorResponseException
     */
    public function getInfo ()
    {
        if (!$this->info) {
            $this->info = $this->client->get($this->uri);
        }

        return $this->info;
    }

    /**
     * @return \Illuminate\Support\Collection|\Tightenco\Collect\Support\Collection
     * @throws ErrorResponseException
     */
    public function getRouteList ()
    {
        return collect($this->getInfo()->routes)->map(function($route) {
            $route->path = $this->cleanRouteParameters(new Uri($route->path));
            return $route;
        });
    }

    /**
     * @throws ErrorResponseException
     */
    public function getRoutes ()
    {
        $allRoutes = $this->getRouteList();

        return $allRoutes;
    }

    /**
     * @param UriInterface $route
     * @return \Illuminate\Support\Collection|\Tightenco\Collect\Support\Collection
     */
    public function getRouteParameters (UriInterface $route)
    {
        $route = $this->cleanRouteParameters($route);
        $matches = [];
        $regex = '/{(.*?)}/i';
        preg_match_all($regex, urldecode($route->getPath()), $matches, PREG_SET_ORDER);

        $parameters = [];
        foreach ($matches as $placeholder) {
            if ($placeholder && count($placeholder) > 1) {
                $parameters[] = $placeholder[1];
            }
        }

        return collect($parameters);
    }

    /**
     * @param UriInterface $route
     * @return UriInterface
     */
    protected function cleanRouteParameters (UriInterface $route)
    {
        $path = urldecode($route->getPath());

        $matches = [];
        $regex = '/{(.*?)}/i';
        preg_match_all($regex, $path, $matches, PREG_SET_ORDER);

        foreach ($matches as $placeholder) {
            if ($placeholder && count($placeholder) > 1) {
                $parts = explode(":", $placeholder[1]);
                $param = array_shift($parts);
                $path = str_ireplace($placeholder[1], $param, $path);
            }
        }

        return $route->withPath($path);
    }
}
