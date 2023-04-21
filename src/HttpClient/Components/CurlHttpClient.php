<?php

namespace Kaa\HttpClient\Components;
use Kaa\HttpClient\Contracts\HttpClientInterface;

class CurlHttpClient implements HttpClientInterface
{
    use HttpClientTrait;

    private array $defaultOptions = self::OPTIONS_DEFAULTS + [
        'auth_ntlm' => null, // array|string - an array containing the username as first value, and optionally the
        'extra' => [
            'curl' => [],
        ],
    ];
    private static array $emptyDefaults = self::OPTIONS_DEFAULTS + ['auth_ntlm' => null];
    //private $multi;
    //private static $curlVersion;

    public function __construct(array $defaultOptions = [], int $maxHostConnections = 6, int $maxPendingPushes = 50)
    {

    }
}