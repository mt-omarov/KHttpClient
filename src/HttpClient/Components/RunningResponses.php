<?php

namespace Kaa\HttpClient\Components;
use Kaa\HttpClient\Components\CurlClientState;

class RunningResponses
{
    public CurlClientState $multi;
    /** @var array<int, CurlResponse> $responses */
    public array $responses;

    public function __construct(CurlClientState $multi)
    {
        $this->multi = $multi;
        $this->responses = [];
    }

    public function setResponse(int $id, CurlResponse $response)
    {
        $this->responses[$id] = $response;
    }
}