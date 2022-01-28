<?php

namespace Weble\RevisoApi;

use Psr\Http\Message\UriInterface;

class Collection extends \Illuminate\Support\Collection
{
    protected string $keyName;
    protected object $info;

    public function __construct(Client $client, object $list, string $keyName)
    {
        $this->info = $list;
        $this->keyName = $keyName;

        parent::__construct($this->info->collection ?? []);

        $this->map(fn($item) => new Model($client, $this->keyName, $item))
            ->keyBy(fn(Model $item) => $item->{$this->keyName});
    }

    public function getMetadata(): ?object
    {
        return $this->info->metadata;
    }

    public function getPagination(): ?object
    {
        return $this->info->pagination;
    }

    public function getUrl(): UriInterface
    {
        return Client::createUri($this->info->self);
    }
}
