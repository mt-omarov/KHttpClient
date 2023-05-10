<?php

namespace Kaa\HttpClient\Components;

class CurlClientState extends ClientState
{
    public ?CurlMultiHandle $handle;

    /** @var PushedResponse[] */
    public $pushedResponses = [];
    /** @var DnsCache */
    public $dnsCache;

    public function __construct(int $maxHostConnections, int $maxPendingPushes)
    {
        $this->handle = new CurlMultiHandle();
        $this->dnsCache = new DnsCache();
        $this->reset();

        $this->handle->curlSetOpt(\CURLMOPT_PIPELINING, \CURLPIPE_MULTIPLEX);
        $maxHostConnections = $this->handle->curlSetOpt(\CURLMOPT_MAX_HOST_CONNECTIONS, 0 < $maxHostConnections ? $maxHostConnections : \PHP_INT_MAX) ? 0 : $maxHostConnections;
        $this->handle->curlSetOpt(\CURLMOPT_MAXCONNECTS, $maxHostConnections);

        if (0 >= $maxPendingPushes) {
            return;
        }

        // Неизвестно, требуется ли производить клонирование во избежание циркулярной зависимости при установке curlSetOpt.
        // В KPHP отсутствует возможность установки функций через curl_setopt на хендлеры => необходимо реализовать
        // возможность установки функции для автоматической обработки поступающих от сервера запрос-ответов.
    }

    public function reset(): void
    {
        foreach ($this->pushedResponses as $response) {
            $this->handle->curlMultiRemoveHandle($response->handle->getHandle());
            $response->handle->curlClose();
        }

        $this->pushedResponses = [];
        $this->dnsCache->evictions = $this->dnsCache->evictions ?: $this->dnsCache->removals;
        $this->dnsCache->removals = $this->dnsCache->hostnames = [];

        //$this->handle->curlSetOpt(CURLMOPT_PUSHFUNCTION, null);
        $active = 0;
        while (CURLM_CALL_MULTI_PERFORM === $this->handle->curlMultiExec($active));
    }

    /**
     * @param CurlHandle $parent
     * @param $pushed
     * @param array $requestHeaders
     * @param int $maxPendingPushes
     * @return int
     */
    public static function handlePush($parent, $pushed, array $requestHeaders, int $maxPendingPushes): int
    {
        $headers = [];
        $origin = $parent->getInfo(\CURLINFO_EFFECTIVE_URL);

        foreach ($requestHeaders as $h) {
            if (false !== $i = strpos($h, ':', 1)) {
                $headers[substr($h, 0, $i)][] = substr($h, 1 + $i);
            }
        }

        if (!isset($headers[':method']) || !isset($headers[':scheme']) || !isset($headers[':authority']) || !isset($headers[':path'])) {
            // опущена работа с логированием через поле $this->logger
            //$this->logger?->debug(sprintf('Rejecting pushed response from "%s": pushed headers are invalid', $origin));
            return \CURL_PUSH_DENY;
        }

        $url = $headers[':scheme'][0].'://'.$headers[':authority'][0];

        if (!str_starts_with($origin, $url.'/')) {
            //$this->logger?->debug(sprintf('Rejecting pushed response from "%s": server is not authoritative for "%s"', $origin, $url));
            return \CURL_PUSH_DENY;
        }

        // Опущена работа с массивом $this->pushedResponses ввиду отсутствия функционала получения индекса через key().
        // Нужно проработать передачу текущего элемента массива в функцию.

//        if ($maxPendingPushes <= \count($this->pushedResponses)) {
//            $fifoUrl = key($this->pushedResponses);
//            unset($this->pushedResponses[$fifoUrl]);
//            $this->logger?->debug(sprintf('Evicting oldest pushed response: "%s"', $fifoUrl));
//        }

        $url .= $headers[':path'][0];
        //$this->logger?->debug(sprintf('Queueing pushed response: "%s"', $url));

        // Опущено использование $this->openHandles из родительского класса.
        // Нужно разобраться с данным полем, указать тип и предназначение.
        //$this->pushedResponses[$url] = new PushedResponse(new CurlResponse($this, $pushed), $headers, $this->openHandles[(int) $parent][1] ?? [], $pushed);

        return \CURL_PUSH_OK;
    }
}