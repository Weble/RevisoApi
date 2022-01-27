<?php
namespace Webleit\RevisoApi;
use GuzzleHttp\Psr7\Uri;
use Psr\Http\Message\UriInterface;

/**
 * Class Model
 * @package Webleit\RevisoApi
 */
class EmptyModel extends Model
{
    /**
     * @var UriInterface
     */
    protected $uri;

    /**
     * EmptyModel constructor.
     * @param $keyName
     */
    public function __construct (UriInterface $uri, $keyName)
    {
        $this->uri = $uri;
        $this->keyName = $keyName;
        $this->data = collect();
        $this->client = Client::getInstance();
    }

    /**
     * @return UriInterface
     */
    public function getUrl()
    {
        return $this->uri;
    }


    /**
     * is a new object?
     * @return bool
     */
    public function isNew()
    {
        return true;
    }

    /**
     * Get the id of the object
     * @return null
     */
    public function getId()
    {
        return null;
    }

    /**
     * @param array $data
     * @return Model
     * @throws Exceptions\ErrorResponseException
     */
    public function save($data = [])
    {
        $this->data = $this->data->merge($data);

        $data = $this->client->post($this->getUrl(), $this->data->toArray());

        return new Model($this->getKeyName(), $data);
    }
}