<?php

namespace Webleit\RevisoApi;

use GuzzleHttp\Psr7\Uri;
use Psr\Http\Message\UriInterface;

/**
 * Class Reviso
 * @package Webleit\RevisoApi
 */
class Collection
{
    /**
     * @var Collection
     */
    protected $data = [];

    /**
     * @var string
     */
    protected $keyName;

    /**
     * @var \stdClass
     */
    protected $info;

    /**
     * Resource constructor.
     * @param object $list
     * @param string $keyName
     */
    public function __construct ($list, $keyName)
    {
        $this->info = $list;
        $this->keyName = $keyName;
    }

    /**
     * @return \Tightenco\Collect\Support\Collection
     */
    public function getData()
    {
        if (!$this->data) {

            $this->data = collect($this->info->collection)->map(function($item) {
                return new Model($item, $this->keyName);
            })->keyBy(function(Model $item) {
                return $item->{$this->keyName};
            });
        }

        return $this->data;
    }

    /**
     * @param $name
     * @param $arguments
     * @return mixed
     */
    public function __call ($name, $arguments)
    {
        return call_user_func_array([$this->getData(), $name], $arguments);
    }

    /**
     * @return mixed
     */
    public function getMetadata()
    {
        return $this->info->metadata;
    }

    /**
     * @return mixed
     */
    public function getPagination()
    {
        return $this->info->pagination;
    }

    /**
     * @return UriInterface
     */
    public function getUrl()
    {
        return new Uri($this->info->self);
    }
}