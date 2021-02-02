<?php

namespace Webleit\RevisoApi\Endpoint;

use GuzzleHttp\Psr7\Uri;
use Psr\Http\Message\UriInterface;
use Webleit\RevisoApi\Client;
use Webleit\RevisoApi\Collection;
use Webleit\RevisoApi\Exceptions\ErrorResponseException;

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
     * @var array
     */
    protected $filters = [];

    /**
     * ListEndpoint constructor.
     * @param Client $client
     * @param UriInterface $uri
     * @param $resourceKey
     */
    public function __construct(Client $client, UriInterface $uri, $resourceKey)
    {
        $this->client = $client;
        $this->uri = $uri;
        $this->resourceKey = $resourceKey;
    }

    /**
     * @param array $filters
     * @return Collection
     * @throws ErrorResponseException
     */
    public function get(): Collection
    {
        $uri = Uri::withQueryValue($this->uri, 'skippages', $this->page);
        $uri = Uri::withQueryValue($uri, 'pagesize', $this->perPage);

        $filtersQuery = $this->buildFilters();
        if ($filtersQuery) {
            $uri = Uri::withQueryValue($uri, 'filter', $this->buildFilters());
        }

        $list = $this->client->get($uri);

        return new Collection($list, $this->getResourceKey());
    }

    /**
     * @param string $filterName
     * @param string $operator
     * @param $value
     * @return $this
     */
    public function where(string $filterName, string $operator, $value): self
    {
        $this->filters[] = [
            'name'     => $filterName,
            'operator' => $operator,
            'value'    => $value
        ];

        return $this;
    }

    /**
     * @param $number
     * @return $this
     */
    public function perPage($number)
    {
        $this->perPage = $number;
        return $this;
    }

    /**
     * @param $number
     * @return $this
     */
    public function page($number)
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

    /**
     * @return string|null
     */
    protected function buildFilters(): ?string
    {
        if (!count($this->filters)) {
            return null;
        }

        $operatorsMap = [
            "="     => '$eq',
            "!="    => '$ne',
            ">"     => '$gt',
            ">="    => '$gte',
            "<"     => '$lt',
            "<="    => '$lte',
            "like"  => '$like',
            "&&"    => '$and',
            "||"    => '$or',
            "in"    => '$in',
            "notIn" => '$nin',
        ];

        $query = '';
        foreach ($this->filters as $filter) {
            if (isset($operatorsMap[$filter['operator']])) {
                $filter['operator'] = $operatorsMap[$filter['operator']];
            }

            $query .= $filter['name'] . $filter['operator'] . ':' . $filter['value'];
        }

        return $query;
    }
}
