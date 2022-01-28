<?php

namespace Weble\RevisoApi;

use Illuminate\Contracts\Support\Arrayable;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Message\UriInterface;
use Weble\RevisoApi\Endpoint\ListEndpoint;

class Model implements \JsonSerializable, Arrayable
{
    public const SELF = 'self';
    protected \Illuminate\Support\Collection $data;
    protected string $keyName;
    protected Client $client;

    public function __construct(Client $client, string $keyName, ?object $data = null)
    {
        $this->data = new \Illuminate\Support\Collection($data ?? []);
        $this->keyName = $keyName;

        $this->client = $client;
    }

    public function getUrl(): UriInterface
    {
        return Client::createUri($this->getData()->get(static::SELF, ''));
    }

    public function getKeyName(): string
    {
        return $this->keyName;
    }

    public function __get($name)
    {
        if (! $this->getData()->has($name)) {
            return null;
        }

        // Is url => let's fetch that resource
        if (Client::isRevisoApiUrl($this->getData()->get($name)) && $name != static::SELF) {
            return new ListEndpoint($this->client, Client::createUri($this->getData()->get($name)), $this->getKeyName());
        }

        return $this->getData()->get($name);
    }

    public function __set($name, $value)
    {
        $this->data->put($name, $value);
    }

    public function getData(): \Illuminate\Support\Collection
    {
        return $this->data;
    }

    public function toArray(): array
    {
        return $this->getData()->toArray();
    }

    public function toJson(int $options = 0): string
    {
        return $this->getData()->toJson($options);
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    public function isNew(): bool
    {
        return ! $this->getId();
    }

    public function getId(): int|string|null
    {
        $key = $this->getKeyName();

        return $this->$key ?: false;
    }

    public function save(array $data = []): Model
    {
        foreach ($data as $key => $value) {
            $this->data->$key = $value;
        }

        $data = $this->client->put($this->getUrl(), (array)$this->data);
        $this->data = (new Model($this->client, $this->getKeyName(), $data))->getData();

        return $this;
    }

    /**
     * @throws Exceptions\ErrorResponseException
     * @throws ClientExceptionInterface
     */
    public function delete(): void
    {
        $this->client->delete($this->getUrl());
    }
}
