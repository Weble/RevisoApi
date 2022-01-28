<?php

namespace Weble\RevisoApi;

use Psr\Http\Message\UriInterface;

class Collection extends \Illuminate\Support\Collection
{
    protected string $keyName;
    protected object $info;

    public static function create(Client $client, object $info, string $keyName): static
    {
        return (new static((array) ($info->collection ?? [])))
            ->withInfo($info)
            ->map(fn ($item) => new Model($client, $keyName, $item))
            ->keyBy(fn (Model $item) => $item->getData()->get($keyName));
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

    protected function withInfo(object $info): static
    {
        $this->info = $info;

        return $this;
    }
}
