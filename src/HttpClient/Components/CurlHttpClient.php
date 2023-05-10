<?php

namespace Kaa\HttpClient\Components;
use Kaa\HttpClient\Contracts\HttpClientInterface;
use Kaa\HttpClient\Components\AbstractHttpClient;
use Kaa\HttpClient\Components\CurlClientState;
use Kaa\HttpClient\Contracts\ResponseInterface;
use Kaa\HttpClient\Components\Exception\InvalidArgumentException;

class CurlHttpClient extends AbstractHttpClient implements HttpClientInterface
{
    private Options $defaultOptions;
    private CurlClientState $multi;
    // public ?LoggerInterface $logger = null;
    public function __construct(?Options $defaultOptions = null, int $maxHostConnections = 6, int $maxPendingPushes = 50)
    {
        $this->defaultOptions = new Options();
        $this->defaultOptions->setExtra(['curl'=>[]]);

        if ($defaultOptions) {
            [, $this->defaultOptions] = self::prepareRequest(null, null, $defaultOptions, $this->$defaultOptions);
        }
        $this->multi = new CurlClientState($maxHostConnections, $maxPendingPushes);

//        curl_multi_setopt($this->multi->handle, CURLMOPT_PUSHFUNCTION, function ($parent, $pushed, array $requestHeaders) use ($maxPendingPushes) {
//            return $this->handlePush($parent, $pushed, $requestHeaders, $maxPendingPushes);
//        });
    }

    public function getMulti(): CurlClientState
    {
        return $this->multi;
    }

//    /**
//     * @param mixed $parent
//     * @param mixed $pushed
//     * @param array $requestHeaders
//     * @param int $maxPendingPushes
//     * @return int
//     */
//    private function handlePush($parent, $pushed, array $requestHeaders, int $maxPendingPushes): int
//    {
//        $headers = [];
//        $origin = curl_getinfo($parent, CURLINFO_EFFECTIVE_URL);
//
//        foreach ($requestHeaders as $h) {
//            if (false !== $i = strpos($h, ':', 1)) {
//                $headers[substr($h, 0, $i)][] = substr($h, 1 + $i);
//            }
//        }
//
//        if (!isset($headers[':method']) || !isset($headers[':scheme']) || !isset($headers[':authority']) || !isset($headers[':path'])) {
//            $this->logger && $this->logger->debug(sprintf('Rejecting pushed response from "%s": pushed headers are invalid', $origin));
//
//            return CURL_PUSH_DENY;
//        }
//
//        $url = $headers[':scheme'][0].'://'.$headers[':authority'][0];
//
//        // curl before 7.65 doesn't validate the pushed ":authority" header,
//        // but this is a MUST in the HTTP/2 RFC; let's restrict pushes to the original host,
//        // ignoring domains mentioned as alt-name in the certificate for now (same as curl).
//        if (0 !== strpos($origin, $url.'/')) {
//            $this->logger && $this->logger->debug(sprintf('Rejecting pushed response from "%s": server is not authoritative for "%s"', $origin, $url));
//
//            return CURL_PUSH_DENY;
//        }
//
//        if ($maxPendingPushes <= \count($this->multi->pushedResponses)) {
//            $fifoUrl = key($this->multi->pushedResponses);
//            unset($this->multi->pushedResponses[$fifoUrl]);
//            $this->logger && $this->logger->debug(sprintf('Evicting oldest pushed response: "%s"', $fifoUrl));
//        }
//
//        $url .= $headers[':path'][0];
//        $this->logger && $this->logger->debug(sprintf('Queueing pushed response: "%s"', $url));
//
//        $this->multi->pushedResponses[$url] = new PushedResponse(new CurlResponse($this->multi, $pushed), $headers, $this->multi->openHandles[(int) $parent][1] ?? [], $pushed);
//
//        return CURL_PUSH_OK;
//    }

    public function request(string $method, string $url, Options $options = new Options()) //ResponseInterface
    {
        [$url, $options] = self::prepareRequest($method, $url, $options, $this->defaultOptions);
        $scheme = $url['scheme'];
        $authority = $url['authority'];
        $host = parse_url($authority, PHP_URL_HOST);
        $url = implode('', $url);

        if (!Options::isset($options->getNormalizedHeader('user-agent'))) {
            $options->addToHeaders('User-Agent: Symfony HttpClient/Curl');
        }

        $curlopts = [
            CURLOPT_URL => $url,
            CURLOPT_TCP_NODELAY => true,
            CURLOPT_PROTOCOLS => CURLPROTO_HTTP | CURLPROTO_HTTPS,
            CURLOPT_REDIR_PROTOCOLS => CURLPROTO_HTTP | CURLPROTO_HTTPS,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 0 < $options->getMaxRedirects() ? $options->getMaxRedirects() : 0,
            CURLOPT_COOKIEFILE => '', // Keep track of cookies during redirects
            CURLOPT_TIMEOUT => 0,
            CURLOPT_PROXY => $options->getProxy(),
            CURLOPT_NOPROXY => $options->getNoProxy() ?? $_SERVER['no_proxy'] ?? $_SERVER['NO_PROXY'] ?? '',
            //CURLOPT_SSL_VERIFYPEER => $options['verify_peer'],
            //CURLOPT_SSL_VERIFYHOST => $options['verify_host'] ? 2 : 0,
            //CURLOPT_CAINFO => $options['cafile'],
            //CURLOPT_CAPATH => $options['capath'],
            //CURLOPT_SSL_CIPHER_LIST => $options['ciphers'],
            //CURLOPT_SSLCERT => $options['local_cert'],
            //CURLOPT_SSLKEY => $options['local_pk'],
            //CURLOPT_KEYPASSWD => $options['passphrase'],
            //CURLOPT_CERTINFO => $options['capture_peer_cert_chain'],
        ];

        if (1.0 === (float) $options->getHttpVersion()) {
            $curlopts[CURLOPT_HTTP_VERSION] = CURL_HTTP_VERSION_1_0;
        } elseif (1.1 === (float) $options->getHttpVersion()) {
            $curlopts[CURLOPT_HTTP_VERSION] = CURL_HTTP_VERSION_1_1;
        } elseif (\defined('CURL_VERSION_HTTP2') && (CURL_VERSION_HTTP2 & self::$curlVersion['features']) && ('https:' === $scheme || 2.0 === (float) $options->getHttpVersion())) {
            $curlopts[CURLOPT_HTTP_VERSION] = CURL_HTTP_VERSION_2_0;
        }

        if (Options::isset($options->getAuthNtml())) {
            $curlopts[CURLOPT_HTTPAUTH] = CURLAUTH_NTLM;
            $curlopts[CURLOPT_HTTP_VERSION] = CURL_HTTP_VERSION_1_1;

            if (\is_array($options->getAuthNtml())) {
                $count = \count($options->getAuthNtml());
                if ($count <= 0 || $count > 2) {
                    throw new InvalidArgumentException(sprintf('Option "auth_ntlm" must contain 1 or 2 elements, %d given.', $count));
                }

                $options->setAuthNtml(implode(':', $options->getAuthNtml()));
            }

            if (!\is_string($options->getAuthNtml())) {
                throw new InvalidArgumentException(sprintf('Option "auth_ntlm" must be a string or an array, "%s" given.', gettype($options->getAuthNtml())));
            }

            $curlopts[CURLOPT_USERPWD] = $options->getAuthNtml();
        }

        if (!ZEND_THREAD_SAFE) {
            $curlopts[CURLOPT_DNS_USE_GLOBAL_CACHE] = false;
        }

        if (\defined('CURLOPT_HEADEROPT')) {
            $curlopts[CURLOPT_HEADEROPT] = CURLHEADER_SEPARATE;
        }

        // curl's resolve feature varies by host:port but ours varies by host only, let's handle this with our own DNS map
        if (isset($this->multi->dnsCache->hostnames[$host])) {
            $options->addToResolve($host, $this->multi->dnsCache->hostnames[$host]);
        }

        if ($options->getResolve() || $this->multi->dnsCache->evictions) {
            // First reset any old DNS cache entries then add the new ones
            $resolve = $this->multi->dnsCache->evictions;
            $this->multi->dnsCache->evictions = [];
            $port = parse_url($authority, PHP_URL_PORT) ?: ('http:' === $scheme ? 80 : 443);

            if ($resolve && 0x072a00 > self::$curlVersion['version_number']) {
                // DNS cache removals require curl 7.42 or higher
                // On lower versions, we have to create a new multi handle
                $this->multi->handle->curlClose();
                $this->multi->handle = (new self())->multi->handle;
            }

            foreach ($options->getResolve() as $host => $ip) {
                $resolve[] = null === $ip ? "-$host:$port" : "$host:$port:$ip";
                $this->multi->dnsCache->hostnames[$host] = $ip;
                $this->multi->dnsCache->removals["-$host:$port"] = "-$host:$port";
            }

            $curlopts[CURLOPT_RESOLVE] = $resolve;
        }

        if ('POST' === $method) {
            // Use CURLOPT_POST to have browser-like POST-to-GET redirects for 301, 302 and 303
            $curlopts[CURLOPT_POST] = true;
        } elseif ('HEAD' === $method) {
            $curlopts[CURLOPT_NOBODY] = true;
        } else {
            $curlopts[CURLOPT_CUSTOMREQUEST] = $method;
        }

        if ('\\' !== \DIRECTORY_SEPARATOR && $options['timeout'] < 1) {
            $curlopts[CURLOPT_NOSIGNAL] = true;
        }

        if (\extension_loaded('zlib') && !Options::isset($options->getNormalizedHeader('accept-encoding'))) {
            $options->addToHeaders('Accept-Encoding: gzip'); // Expose only one encoding, some servers mess up when more are provided
        }

        foreach ($options->getHeaders() as $header) {
            if (':' === $header[-2] && \strlen($header) - 2 === strpos($header, ': ')) {
                // curl requires a special syntax to send empty headers
                $curlopts[CURLOPT_HTTPHEADER][] = substr_replace($header, ';', -2);
            } else {
                $curlopts[CURLOPT_HTTPHEADER][] = $header;
            }
        }

        // Prevent curl from sending its default Accept and Expect headers
        foreach (['accept', 'expect'] as $header) {
            if (!Options::isset($options->getElementNormalizedHeader($header, 0))) {
                $curlopts[CURLOPT_HTTPHEADER][] = $header.':';
            }
        }

//        if (!\is_string($body = $options->getBody())) {
//            if (is_int($body)) {
//                $curlopts[CURLOPT_INFILE] = $body;
//            } # ifndef KPHP
//            elseif (\is_resource($body)) {
//                $curlopts[CURLOPT_INFILE] = $body;
//            } #endif
//            else {
//                $eof = false;
//                $buffer = '';
//                $curlopts[CURLOPT_READFUNCTION] = static function ($ch, $fd, $length) use ($body, &$buffer, &$eof) {
//                    return self::readRequestBody($length, $body, $buffer, $eof);
//                };
//            }
//        }
//
//            if (isset($options['normalized_headers']['content-length'][0])) {
//                $curlopts[CURLOPT_INFILESIZE] = substr($options['normalized_headers']['content-length'][0], \strlen('Content-Length: '));
//            } elseif (!isset($options['normalized_headers']['transfer-encoding'])) {
//                $curlopts[CURLOPT_HTTPHEADER][] = 'Transfer-Encoding: chunked'; // Enable chunked request bodies
//            }
//
//            if ('POST' !== $method) {
//                $curlopts[CURLOPT_UPLOAD] = true;
//            }
//        } elseif ('' !== $body || 'POST' === $method) {
//            $curlopts[CURLOPT_POSTFIELDS] = $body;
//        }
//
//        if ($options['peer_fingerprint']) {
//            if (!isset($options['peer_fingerprint']['pin-sha256'])) {
//                throw new TransportException(__CLASS__.' supports only "pin-sha256" fingerprints.');
//            }
//
//            $curlopts[CURLOPT_PINNEDPUBLICKEY] = 'sha256//'.implode(';sha256//', $options['peer_fingerprint']['pin-sha256']);
//        }
//
//        if ($options['bindto']) {
//            $curlopts[file_exists($options['bindto']) ? CURLOPT_UNIX_SOCKET_PATH : CURLOPT_INTERFACE] = $options['bindto'];
//        }
//
//        if (0 < $options['max_duration']) {
//            $curlopts[CURLOPT_TIMEOUT_MS] = 1000 * $options['max_duration'];
//        }
//
//        if ($pushedResponse = $this->multi->pushedResponses[$url] ?? null) {
//            unset($this->multi->pushedResponses[$url]);
//
//            if (self::acceptPushForRequest($method, $options, $pushedResponse)) {
//                $this->logger && $this->logger->debug(sprintf('Accepting pushed response: "%s %s"', $method, $url));
//
//                // Reinitialize the pushed response with request's options
//                $ch = $pushedResponse->handle;
//                $pushedResponse = $pushedResponse->response;
//                $pushedResponse->__construct($this->multi, $url, $options, $this->logger);
//            } else {
//                $this->logger && $this->logger->debug(sprintf('Rejecting pushed response: "%s"', $url));
//                $pushedResponse = null;
//            }
//        }
//
//        if (!$pushedResponse) {
//            $ch = curl_init();
//            $this->logger && $this->logger->info(sprintf('Request: "%s %s"', $method, $url));
//        }
//
//        foreach ($curlopts as $opt => $value) {
//            if (null !== $value && !curl_setopt($ch, $opt, $value) && CURLOPT_CERTINFO !== $opt) {
//                $constants = array_filter(get_defined_constants(), static function ($v, $k) use ($opt) {
//                    return $v === $opt && 'C' === $k[0] && (0 === strpos($k, 'CURLOPT_') || 0 === strpos($k, 'CURLINFO_'));
//                }, ARRAY_FILTER_USE_BOTH);
//
//                throw new TransportException(sprintf('Curl option "%s" is not supported.', key($constants) ?? $opt));
//            }
//        }

        //return $pushedResponse ?? new CurlResponse($this->multi, $ch, $options, $this->logger, $method, self::createRedirectResolver($options, $host), self::$curlVersion['version_number']);
    }

}