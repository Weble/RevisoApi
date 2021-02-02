<?php

namespace Webleit\RevisoApi\Endpoint;

use GuzzleHttp\Psr7\Uri;
use Psr\Http\Message\UriInterface;
use Webleit\RevisoApi\Client;
use Webleit\RevisoApi\Exceptions\ErrorResponseException;
use Webleit\RevisoApi\Collection;

/**
 * Class Reviso
 * @package Webleit\RevisoApi
 */
class ProjectEndpoint extends Endpoint
{
    /**
     * @return \Illuminate\Support\Collection|\Tightenco\Collect\Support\Collection
     * @throws ErrorResponseException
     */
    public function getRouteList ()
    {
        $routes = collect($this->getInfo()->routes)->map(function($route) {
            $route->path = $this->cleanRouteParameters(new Uri($route->path));
            return $route;
        });

        // First route on projects is not good
        $routes->shift();

        return $routes;
    }
}