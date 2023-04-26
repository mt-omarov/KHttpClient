<?php

namespace Kaa\HttpClient\Components;
use Kaa\HttpClient\Contracts\HttpClientInterface;
use Kaa\HttpClient\Components\HttpClientTrait;
use Kaa\HttpClient\Components\CurlClientState;
use kCurl\HttpClient\Contracts\ResponseInterface;

class CurlHttpClient extends HttpClientTrait implements HttpClientInterface
{
    private Options $defaultOptions;
    public function __construct(?Options $defaultOptions, int $maxHostConnections = 6, int $maxPendingPushes = 50)
    {
        $this->defaultOptions = new Options();
        $this->defaultOptions->setExtra(['curl'=>[]]);

        if ($defaultOptions) {
            [, $this->defaultOptions] = self::prepareRequest(null, null, $defaultOptions, $this->$defaultOptions);
        }
        $this->multi = new CurlClientState($maxHostConnections, $maxPendingPushes);
    }
}