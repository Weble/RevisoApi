<?php

namespace Weble\RevisoApi;

use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Message\UriInterface;

class EmptyModel extends Model
{
    protected UriInterface $uri;

    public function __construct(Client $client, UriInterface $uri, string $keyName)
    {
        parent::__construct($client, $keyName);

        $this->uri = $uri;
    }

    public function getUrl(): UriInterface
    {
        return $this->uri;
    }

    public function isNew(): bool
    {
        return true;
    }

    public function getId(): int|string|null
    {
        return null;
    }

    /**
     * @throws Exceptions\ErrorResponseException
     * @throws ClientExceptionInterface
     */
    public function save(array $data = []): Model
    {
        $this->data = $this->data->merge($data);

        $data = $this->client->post($this->getUrl(), $this->data->toArray());

        return new Model($this->client, $this->getKeyName(), $data);
    }
}
