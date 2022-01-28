<?php

namespace Weble\RevisoApi\Endpoint;

use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Message\UriInterface;
use Weble\RevisoApi\Client;
use Weble\RevisoApi\Collection;
use Weble\RevisoApi\Exceptions\ErrorResponseException;

class ListEndpoint
{
    public const PARAM_PAGE_SKIP = 'skippages';
    public const PARAM_PAGE_SIZE = 'pagesize';
    protected Client $client;
    protected UriInterface $uri;
    protected object $info;
    protected int $perPage = 20;
    protected int $page = 0;
    protected string $resourceKey;
    protected array $filters = [];

    public function __construct(Client $client, UriInterface $uri, string $resourceKey)
    {
        $this->client = $client;
        $this->uri = $uri;
        $this->resourceKey = $resourceKey;
    }

    /**
     * @throws ErrorResponseException
     * @throws ClientExceptionInterface
     */
    public function get(): Collection
    {
        $uri = Client::appendParameters($this->uri, [
            static::PARAM_PAGE_SKIP => $this->page,
            static::PARAM_PAGE_SIZE => $this->perPage,
        ]);

        $filtersQuery = $this->buildFilters();
        if ($filtersQuery) {
            $uri = Client::appendParameters($uri, ['filter' => $this->buildFilters()]);
        }

        $list = $this->client->get($uri);

        return Collection::create($this->client, $list, $this->getResourceKey());
    }

    public function where(string $filterName, string $operator, mixed $value): static
    {
        $this->filters[] = [
            'name' => $filterName,
            'operator' => $operator,
            'value' => $value,
        ];

        return $this;
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

    public function getResourceKey(): string
    {
        return $this->resourceKey;
    }

    protected function buildFilters(): ?string
    {
        if (! count($this->filters)) {
            return null;
        }

        $operatorsMap = [
            "=" => '$eq',
            "!=" => '$ne',
            ">" => '$gt',
            ">=" => '$gte',
            "<" => '$lt',
            "<=" => '$lte',
            "like" => '$like',
            "&&" => '$and',
            "||" => '$or',
            "in" => '$in',
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
