<?php

namespace Weble\RevisoApi\Endpoint;

use Illuminate\Support\Collection;
use Psr\Http\Client\ClientExceptionInterface;
use Weble\RevisoApi\Client;
use Weble\RevisoApi\Exceptions\ErrorResponseException;

class ProjectEndpoint extends Endpoint
{
    /**
     * @throws ErrorResponseException
     * @throws ClientExceptionInterface
     */
    public function getRouteList(): Collection
    {
        $routes = (new Collection($this->getInfo()->routes))
            ->map(function ($route) {
                $route->path = $this->cleanRouteParameters(Client::createUri($route->path));

                return $route;
            });

        // First route on projects is not good
        $routes->shift();

        return $routes;
    }
}
