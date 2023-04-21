<?php

namespace Kaa\HttpClient\Contracts;

interface HttpClientInterface
{
    /** @var mixed */
    public const OPTIONS_DEFAULTS = [
        'auth_basic' => null,   // array|string - an array containing the username as first value, and optionally the
                                //   password as the second one; or string like username:password - enabling HTTP Basic
                                //   authentication (RFC 7617)
        'auth_bearer' => null,  // string - a token enabling HTTP Bearer authorization (RFC 6750)
        'query' => [],          // string[] - associative array of query string values to merge with the request's URL
        'headers' => [],        // string[]|string[][] - headers names provided as keys or as part of values
        'body' => '',           // array|string|\Closure - the callback SHOULD yield a string
                                //   smaller than the amount requested as argument; the empty string signals EOF; if
                                //   an array is passed, it is meant as a form payload of field names and values
        'json' => null,         // mixed - if set, implementations MUST set the "body" option to the JSON-encoded
                                //   value and set the "content-type" header to a JSON-compatible value if it is not
                                //   explicitly defined in the headers option - typically "application/json"
        'user_data' => null,    // mixed - any extra data to attach to the request (scalar, callable, object...) that
                                //   MUST be available via $response->getInfo('user_data') - not used internally
        'max_redirects' => 20,  // int - the maximum number of redirects to follow; a value lower than or equal to 0
                                //   means redirects should not be followed; "Authorization" and "Cookie" headers MUST
                                //   NOT follow except for the initial host name
        'http_version' => null, // string - defaults to the best supported version, typically 1.1 or 2.0
        'base_uri' => null,     // string - the URI to resolve relative URLs, following rules in RFC 3986, section 2
        'buffer' => true,       // bool|resource|\Closure - whether the content of the response should be buffered or not,
                                //   or a stream resource where the response body should be written,
                                //   or a closure telling if/where the response should be buffered based on its headers
        'on_progress' => null,  // callable(int $dlNow, int $dlSize, array $info) - throwing any exceptions MUST abort
                                //   the request; it MUST be called on DNS resolution, on arrival of headers and on
                                //   completion; it SHOULD be called on upload/download of data and at least 1/s
        'resolve' => [],        // string[] - a map of host to IP address that SHOULD replace DNS resolution
        'proxy' => null,        // string - by default, the proxy-related env vars handled by curl SHOULD be honored
        'no_proxy' => null,     // string - a comma separated list of hosts that do not require a proxy to be reached
        'timeout' => null,      // float - the idle timeout (in seconds) - defaults to ini_get('default_socket_timeout')
        'max_duration' => 0,    // float - the maximum execution time (in seconds) for the request+response as a whole;
                                //   a value lower than or equal to 0 means it is unlimited
        'bindto' => '0',        // string - the interface or the local socket to bind to
        'verify_peer' => true,  // see https://php.net/context.ssl for the following options
        'verify_host' => true,
        'cafile' => null,
        'capath' => null,
        'local_cert' => null,
        'local_pk' => null,
        'passphrase' => null,
        'ciphers' => null,
        'peer_fingerprint' => null,
        'capture_peer_cert_chain' => false,
        'extra' => [],          // array - additional options that can be ignored if unsupported, unlike regular options
    ];

//    public function request(string $method, string $url, array $options = []): ResponseInterface;
//    public function stream(ResponseInterface|iterable $responses, float $timeout = null): ResponseStreamInterface;
//    public function withOptions(array $options): static;
}
