<?php

namespace Kaa\HttpClient\Components;

class ClientState
{
    /** @var array<int,HandleActivity[]> $handlesActivity */
    public array $handlesActivity = [];

    /** @var array<CurlHandle> $openHandles */
    public array $openHandles = [];
    public ?float $lastTimeout = null;
}