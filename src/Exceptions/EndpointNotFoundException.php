<?php

namespace Weble\RevisoApi\Exceptions;

use RuntimeException;

class EndpointNotFoundException extends RuntimeException
{
    public function __construct(string $endpointName, array $endpoints = [])
    {
        parent::__construct("Endpoint {$endpointName} not found. Available endpoints are: " . implode(", ", $endpoints), 404);
    }
}
