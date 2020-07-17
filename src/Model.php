<?php

namespace Webleit\RevisoApi;

use GuzzleHttp\Psr7\Uri;
use Psr\Http\Message\UriInterface;
use Tightenco\Collect\Contracts\Support\Arrayable;
use Webleit\RevisoApi\Endpoint\ListEndpoint;

/**
 * Class Model
 * @package Webleit\RevisoApi
 */
class Model implements \JsonSerializable, Arrayable
{
    /**
     * @var object
     */
    protected $data;

    /**
     * @var string
     */
    protected $keyName;

    /**
     * @var Client
     */
    protected $client;

    /**
     * Model constructor.
     * @param $data
     * @param $keyName
     */
    public function __construct($data = null, $keyName)
    {
        $this->data = $data;
        $this->keyName = $keyName;

        $this->client = Client::getInstance();
    }

    /**
     * @return UriInterface
     */
    public function getUrl()
    {
        return new Uri($this->data->self);
    }

    /**
     * @return string
     */
    public function getKeyName()
    {
        return $this->keyName;
    }

    /**
     * @param $name
     * @return ListEndpoint
     */
    public function __get($name)
    {
        if (!isset($this->data->$name)) {
            return;
        }

        // Is url => let's fetch that resource
        if (Client::isRevisoApiUrl($this->data->$name) && $name != 'self') {
            return new ListEndpoint(Client::getInstance(), new Uri($this->data->$name), $this->getKeyName());
        }

        return $this->data->$name;
    }

    /**
     * @param $name
     * @param $value
     */
    public function __set($name, $value)
    {
        $this->data->$name = $value;
    }

    /**
     * @return object
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return json_decode(json_encode($this->getData()), true);
    }

    /**
     * @return string
     */
    public function toJson()
    {
        return json_encode($this->toArray());
    }

    /**
     * Convert the object into something JSON serializable.
     *
     * @return array
     */
    public function jsonSerialize()
    {
        return $this->toArray();
    }

    /**
     * is a new object?
     * @return bool
     */
    public function isNew()
    {
        return !$this->getId();
    }

    /**
     * Get the id of the object
     * @return bool|string
     */
    public function getId()
    {
        $key = $this->getKeyName();
        return $this->$key ? $this->$key : false;
    }

    /**
     * @param array $data
     * @return Model
     */
    public function save($data = [])
    {
        foreach ($data as $key => $value) {
            $this->data->$key = $value;
        }

        $data = $this->client->put($this->getUrl(), (array)$this->data);

        $updated = new Model($data, $this->getKeyName());
        $this->data = $updated->getData();

        unset($updated);

        return $this;
    }

    /**
     * @return \stdClass|string
     * @throws Exceptions\ErrorResponseException
     */
    public function delete()
    {
        return $this->client->delete($this->getUrl());
    }
}
