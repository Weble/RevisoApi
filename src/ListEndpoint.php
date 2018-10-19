<?php

namespace Webleit\RevisoApi;

use GuzzleHttp\Psr7\Uri;
use Psr\Http\Message\UriInterface;

/**
 * Class Reviso
 * @package Webleit\RevisoApi
 */
class ListEndpoint
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
     * @var string
     */
    protected $resourceKey;

    /**
     * ListEndpoint constructor.
     * @param Client $client
     * @param UriInterface $uri
     * @param $resourceKey
     */
    public function __construct (Client $client, UriInterface $uri, $resourceKey)
    {
        $this->client = $client;
        $this->uri = $uri;
        $this->resourceKey = $resourceKey;
    }

    /**
     * @return Collection
     * @throws Exceptions\ErrorResponseException
     */
    public function get ()
    {
        $uri = Uri::withQueryValue($this->uri, 'skippages', $this->page);
        $uri = Uri::withQueryValue($uri, 'pagesize', $this->perPage);

        $list = $this->client->get($uri);

        return new Collection($list, $this->getResourceKey());
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
     * @return string
     */
    public function getResourceKey()
    {
        return $this->resourceKey;
    }
}