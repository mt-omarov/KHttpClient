<?php

namespace Kaa\HttpClient\Components;

final class PushedResponse
{
    public CurlResponse $response;

    /** @var string[] */
    public array $requestHeaders;
    public array $parentOptions = [];

    /** @var CurlHandle */
    public $handle;

    public function __construct(CurlResponse $response, array $requestHeaders, array $parentOptions, $handle)
    {
        $this->response = $response;
        $this->requestHeaders = $requestHeaders;
        $this->parentOptions = $parentOptions;
        $this->handle = $handle;
    }
}