<?php

namespace Kaa\HttpClient\Components;
use Kaa\HttpClient\Components\Exception\TransportException;
use Kaa\HttpClient\Contracts\ResponseInterface;

class CurlResponse implements ResponseInterface
{
    private CurlClientState $multi;
    private int $id;
    private $headers = [];

    /** @var mixed $info */
    private $info = [
        'response_headers' => [],
        'http_code' => 0,
        'error' => null,
        'canceled' => false,
    ];
    private CurlHandle $handle;
    private int $timeout = 0;
    private int $offset = 0;
    /** @var mixed $finalInfo */
    private $finalInfo = []; // переменная не может быть использована

    /** @var mixed $debugBuffer */
    private $debugBuffer; // переменная не может быть использована
    private static bool $performing = false;

    public function __construct(CurlClientState $multi, CurlHandle $ch, ?array $options = null, string $method = 'GET')
    {
        $this->multi = $multi;
        $this->handle = $ch;

        // попытка использовать FFI для создания временного файла через временную директорию
        //BoostFilesystem::load();
        //$libboost = new BoostFilesystem();
        //$this->debugBuffer = tempnam($libboost->SysGetTempDirPath(), "temp");

        // попытка использовать файл и направить вывод об ошибках потока в него
        //$this->debugBuffer = fopen('debugBuffer', 'w+');
        $ch->curlSetOpt(CURLOPT_VERBOSE, true);
        //$ch->curlSetOpt(CURLOPT_STDERR, $this->debugBuffer); //отсутствует в KPHP

        $this->id = $id = (int) $ch->getHandle();
        self::stream([], null);

        // не закончено
    }

//    public function getInfo(string $type = null)
//    {
//        if (!$this->finalInfo) {
//            $info = array_merge($this->finalInfo, $this->handle->getInfo());
//            $info['url'] = $this->info['url'] ?? $info['url'];
//            $info['redirect_url'] = $this->info['redirect_url'] ?? null;
//
//            // workaround curl not subtracting the time offset for pushed responses
//            if (isset($this->info['url']) && $info['start_time'] / 1000 < $info['total_time']) {
//                $info['total_time'] -= $info['starttransfer_time'] ?: $info['total_time'];
//                $info['starttransfer_time'] = 0.0;
//            }
//
//            $info['debug'] = '';
//            while (!feof($this->debugBuffer)) {
//            $info['debug'] .= fread($this->debugBuffer, 8192);
//            }
//            $waitFor = $this->handle->getInfo( CURLINFO_PRIVATE);
//
//            if (!is_bool($waitFor)){
//                if ('H' !== $waitFor[0] && 'C' !== $waitFor[0]) {
//                    $this->handle->curlSetOpt(CURLOPT_VERBOSE, false);
//                    fseek($this->debugBuffer, 0);
//                    fwrite($this->debugBuffer, "");
//                    fflush($this->debugBuffer);
//                    $this->finalInfo = $info;
//                }
//            }
//        }
//
//        return null !== $type ? $this->finalInfo[$type] ?? null : $this->finalInfo;
//    }

    /**
     * @param CurlResponse $response
     * @param array<int, RunningResponses> $runningResponses
     * @return void
     */
    private static function schedule(self $response, array &$runningResponses): void
    {
        if (isset($runningResponses[$i = (int) $response->multi->handle->getHandle()])) {
            $runningResponses[$i]->setResponse($response->id, $response);
        } else {
            $runningResponses[$i] = new RunningResponses($response->multi);
            $runningResponses[$i]->setResponse($response->id, $response);
        }
        // get_info не работает => нельзя понять, выполнился запрос или нет
        //
//        if ('_0' === $response->handle->getInfo(CURLINFO_PRIVATE)) {
//            // Response already completed
//            $response->multi->handlesActivity[$response->id][] = null;
//            $response->multi->handlesActivity[$response->id][] = null !== $response->info['error'] ? (new HandleActivity())->setError(new TransportException($response->info['error'])) : null;
//        }
    }

    /**
     * @param array<self> $responses
     * @param float|null $timeout
     * @return void
     */
    public static function stream(array $responses, ?float $timeout = null)
    {
        /** @var RunningResponses[] $runningResponses */
        $runningResponses = [];

        foreach ($responses as $response) {
            self::schedule($response, $runningResponses);
        }
        $lastActivity = microtime(true);
        $elapsedTimeout = 0;

        foreach ($runningResponses as $i => $runningResponse) {
            $multi = $runningResponse->multi;
            self::perform($multi, $runningResponse->responses);

            foreach ($runningResponse->responses as $j => $response) {
                $timeoutMax = $timeout ?? max($timeoutMax, $response->timeout);
                $timeoutMin = min($timeoutMin, $response->timeout, 1);
                $chunk = false;

                if (isset($multi->handlesActivity[$j])) {
                    // no-op
                } elseif (!isset($multi->openHandles[$j])) {
                    unset($runningResponse->responses[$j]);
                    continue;
                } elseif ($elapsedTimeout >= $timeoutMax) {
                    //$multi->handlesActivity[$j] = [(new HandleActivity())->setErrorChunk(new ErrorChunk($response->offset, null, sprintf('Idle timeout reached for "%s".', $response->getInfo('url'))))];
                } else {
                    continue;
                }

                while ($multi->handlesActivity[$j] ?? false) {
                    $hasActivity = true;
                    $elapsedTimeout = 0;
                    //$chunk = array_shift($multi->handlesActivity[$j]);
                    //if (\is_string($chunk)) {
                    //}
                }
            }
        }

    }

    /**
     * @param CurlClientState $multi
     * @param array<self> $responses
     * @param int|null $index
     * @return void
     */
    private static function perform(ClientState $multi, array &$responses, ?int $index = null): void
    {
        if (self::$performing) {
            if ($responses !== []) {
                $response = $index ? $responses[$index] : array_first_value($responses);
                $multi->handlesActivity[(int)$response->handle->getHandle()][] = null;
                //$multi->handlesActivity[(int)$response->handle->getHandle()][] = (new HandleActivity())->setError(new TransportException(sprintf('Userland callback cannot use the client nor the response while processing "%s".', $response->getInfo((string) CURLINFO_EFFECTIVE_URL))));
            }
            return;
        }
        try {
            self::$performing = true;
            $active = 0;
            while (CURLM_CALL_MULTI_PERFORM === ($err = $multi->handle->curlMultiExec($active)));

            if (\CURLM_OK !== $err) {
                throw new TransportException((string) CurlMultiHandle::curlMultiStrError((int)$err));
            }
            $tMsgCount = -1;
            while ($info = $multi->handle->curlMultiInfoRead($tMsgCount)) {
                $result = $info['result'];
                $id = $info['handle'];
                $ch = new CurlHandle(null, $info['handle']);
                $waitFor = $ch->getInfo(CURLINFO_PRIVATE) ?: '_0';

                if (\in_array($result, [CURLE_SEND_ERROR, CURLE_RECV_ERROR, /*CURLE_HTTP2*/ 16, /*CURLE_HTTP2_STREAM*/ 92], true) && $waitFor[1] && 'C' !== $waitFor[0]) {
                    $multi->handle->curlMultiRemoveHandle($info['handle']);
                    $waitFor[1] = (string) ((int) $waitFor[1] - 1); // decrement the retry counter
                    $ch->curlSetOpt(CURLOPT_PRIVATE, $waitFor);

                    if ('1' === $waitFor[1]) {
                        $ch->curlSetOpt(CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
                    }
                    if (0 === $multi->handle->curlMultiAddHandle($ch->getHandle())){
                        continue;
                    }
                }
                $multi->handlesActivity[$id][] = null;
                $multi->handlesActivity[$id][] = \in_array($result, [CURLE_OK, CURLE_TOO_MANY_REDIRECTS], true) ||
                '_0' === $waitFor ||
                $ch->getInfo( CURLINFO_SIZE_DOWNLOAD) === $ch->getInfo(CURLINFO_CONTENT_LENGTH_DOWNLOAD) ? null : (new HandleActivity())->setError(new TransportException(sprintf('%s for "%s".', CurlMultiHandle::curlMultiStrError($result), $ch->getInfo(CURLINFO_EFFECTIVE_URL))));
            }
        } catch (TransportException $e) {
            //sprintf($e->getMessage());
            self::$performing = false;
        }
    }

}