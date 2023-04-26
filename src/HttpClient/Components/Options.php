<?php

namespace Kaa\HttpClient\Components;
class Options
{
    static array $fields = [
        'authBasic', 'authBearer', 'query', 'headers', 'normalizedHeaders', 'proxy',
        'noProxy', 'timeOut', 'maxDuration', 'bindTo' , 'userData', 'maxRedirects',
        'httpVersion', 'baseUri', 'buffer', 'onProgress', 'resolve', 'json', 'body'];

    /** @var mixed $peerFingerprint */
    private $peerFingerprint = null;

    private string $authBasic = '';
    private string $authBearer = '';

    /** @var string[] $query */
    private array $query = [];

    /** @var array<string, string> $headers */
    private array $headers = [];

    /** @var array<string, array<string>> $normalizedHeaders */
    private array $normalizedHeaders = [[]];

    private string $proxy = '';
    private string $noProxy = '';
    private float $timeOut = 0;
    private float $maxDuration = 0;
    private string $bindTo = '0';


    /** @var mixed $userData  */
    private $userData = null;
    private int $maxRedirects = 20;
    private string $httpVersion = '';
    private string $baseUri = '';

    /** @var mixed $buffer */
    private $buffer = null;

    /** @var ?Callable $onProgress  */
    private $onProgress = null;

    /** @var string[] $resolve */
    private array $resolve = [];
    private string $json = '';

    /** @var mixed $body */
    private $body = '';

    /** @var mixed $extra */
    private array $extra = [];

    /** @var mixed $authNtml */
    private $authNtml = null;

    /** @return mixed */
    public function getPeerFingerprint()
    {
        return $this->peerFingerprint;
    }

    /** @param mixed $fingerprint */
    public function setPeerFingerprint($fingerprint): self
    {
        $this->peerFingerprint = $fingerprint;
        return $this;
    }

    /** @return mixed */
    public function getExtra(): array
    {
        return $this->extra;
    }

    /** @param mixed $extra */
    public function setExtra($extra): self
    {
        $this->extra = $extra;
        return $this;
    }

    /** @param mixed $authNtml */
    public function setAuthNtml($authNtml): self
    {
        $this->authNtml = $authNtml;
        return $this;
    }

    public function getAuthBasic(): string
    {
        return $this->authBasic;
    }

    public function setAuthBasic(string $authBasic): self
    {
        $this->authBasic = $authBasic;
        return $this;
    }

    /**
     * @return string
     */
    public function getAuthBearer(): string
    {
        return $this->authBearer;
    }

    /**
     * @param string $authBearer
     */
    public function setAuthBearer(string $authBearer): self
    {
        $this->authBearer = $authBearer;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getUserData(): mixed
    {
        return $this->userData;
    }

    /**
     * @param mixed $userData
     */
    public function setUserData(mixed $userData): self
    {
        $this->userData = $userData;
        return $this;
    }

    /**
     * @return int
     */
    public function getMaxRedirects(): int
    {
        return $this->maxRedirects;
    }

    /**
     * @param int $maxRedirects
     */
    public function setMaxRedirects(int $maxRedirects): self
    {
        $this->maxRedirects = $maxRedirects;
        return $this;
    }

    /**
     * @return string
     */
    public function getHttpVersion(): string
    {
        return $this->httpVersion;
    }

    /**
     * @param string $httpVersion
     */
    public function setHttpVersion(string $httpVersion): self
    {
        $this->httpVersion = $httpVersion;
        return $this;
    }

    /**
     * @return string
     */
    public function getBaseUri(): string
    {
        return $this->baseUri;
    }

    /**
     * @param string $baseUri
     */
    public function setBaseUri(string $baseUri): self
    {
        $this->baseUri = $baseUri;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getBuffer(): mixed
    {
        return $this->buffer;
    }

    /**
     * @param mixed $buffer
     */
    public function setBuffer(mixed $buffer): self
    {
        $this->buffer = $buffer;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getOnProgress(): mixed
    {
        return $this->onProgress;
    }

    /**
     * @param mixed $onProgress
     */
    public function setOnProgress(mixed $onProgress): self
    {
        $this->onProgress = $onProgress;
        return $this;
    }

    /**
     * @return string
     */
    public function getUrl(): string
    {
        return $this->url;
    }

    /**
     * @param string $url
     */
    public function setUrl(string $url): self
    {
        $this->url = $url;
        return $this;
    }

    /**
     * @return string
     */
    public function getProxy(): string
    {
        return $this->proxy;
    }

    /**
     * @param string $proxy
     */
    public function setProxy(string $proxy): self
    {
        $this->proxy = $proxy;
        return $this;
    }

    /**
     * @return string
     */
    public function getNoProxy(): string
    {
        return $this->noProxy;
    }

    /**
     * @param string $noProxy
     */
    public function setNoProxy(string $noProxy): self
    {
        $this->noProxy = $noProxy;
        return $this;
    }

    /**
     * @return float
     */
    public function getTimeOut(): float
    {
        return $this->timeOut;
    }

    /**
     * @param float $timeOut
     */
    public function setTimeOut(float $timeOut): self
    {
        $this->timeOut = $timeOut;
        return $this;
    }

    /**
     * @return float
     */
    public function getMaxDuration(): float
    {
        return $this->maxDuration;
    }

    /**
     * @param float $maxDuration
     */
    public function setMaxDuration(float $maxDuration): self
    {
        $this->maxDuration = $maxDuration;
        return $this;
    }

    /**
     * @return string
     */
    public function getBindTo(): string
    {
        return $this->bindTo;
    }

    /**
     * @param string $bindTo
     */
    public function setBindTo(string $bindTo): self
    {
        $this->bindTo = $bindTo;
        return $this;
    }

    /**
     * @return string[]
     */
    public function getQuery(): array
    {
        return $this->query;
    }

    /**
     * @param string[] $query
     */
    public function setQuery(array $query): self
    {
        $this->query = $query;
        return $this;
    }

    /**
     * @return array<string, string>
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /** @param array<string, string> $headers */
    public function setHeaders(array $headers):self
    {
        $this->headers = $headers;
        return $this;
    }

    /** @param array<string, array<string>> $normalizedHeaders */
    public function setNormalizedHeaders(array $normalizedHeaders): self
    {
        $this->normalizedHeaders = $normalizedHeaders;
        return $this;
    }

    /** @param array<string> $header */
    public function setNormalizedHeader(string $key, array $header): self
    {
        $this->normalizedHeaders[$key] = $header;
        return $this;
    }

    /**
     * @return array<string, array<string>>
     */
    public function getNormalizedHeaders(): array
    {
        return $this->normalizedHeaders;
    }

    /**
     * @return array<string>|false
     */
    public function getNormalizedHeader(string $key): array|false
    {
        if (array_key_exists($key, $this->normalizedHeaders)) {
            return $this->normalizedHeaders[$key];
        }
        else return false;
    }

    /** @return mixed */
    public function getBody()
    {
        return $this->body;
    }

    /** @param mixed $body*/
    public function setBody($body): self
    {
        $this->body = $body;
        return $this;
    }

    public function getJson(): string
    {
        return $this->json;
    }

    public function setJson(string $json): self
    {
        $this->json = $json;
        return $this;
    }

    /** @return string[] */
    public function getResolve(): array
    {
        return $this->resolve;
    }

    /** @param string[] $resolve */
    public function setResolve(array $resolve):self
    {
        $this->resolve = $resolve;
        return $this;
    }
    public function addToResolve(string $key, string $value): void
    {
        $this->resolve[$key] = $value;
    }

    public static function mergeOptions(self $lOptions, self $rOptions): self
    {
        foreach (self::$fields as $field)
        {
            // если левый "массив" опций не установлен, используй правый
            switch ($field){
                case ("authBasic"):
                    if (!self::isset($lOptions->getAuthBasic()))
                        $lOptions->setAuthBasic($rOptions->getAuthBasic());
                    break;
                case ("authBearer"):
                    if (!self::isset($lOptions->getAuthBearer()))
                        $lOptions->setAuthBearer($rOptions->getAuthBearer());
                    break;
                case ("query"):
                    if (!self::isset($lOptions->getQuery()))
                        $lOptions->setQuery($rOptions->getQuery());
                    break;
                case ("headers"):
                    if (!self::isset($lOptions->getQuery()))
                        $lOptions->setHeaders($rOptions->getHeaders());
                    break;
                case ("normalizedHeaders"):
                    if (!self::isset($lOptions->getNormalizedHeaders()))
                        $lOptions->setNormalizedHeaders($rOptions->getNormalizedHeaders());
                    break;
                case ("proxy"):
                    if (!self::isset($lOptions->getProxy()))
                        $lOptions->setProxy($rOptions->getProxy());
                    break;
                case ("noProxy"):
                    if (!self::isset($lOptions->getNoProxy()))
                        $lOptions->setNoProxy($rOptions->getNoProxy());
                    break;
                case ("timeOut"):
                    if (!self::isset($lOptions->getTimeOut()))
                        $lOptions->setTimeOut($rOptions->getTimeOut());
                    break;
                case ("maxDuration"):
                    if (!self::isset($lOptions->getMaxDuration()))
                        $lOptions->setMaxDuration($rOptions->getMaxDuration());
                    break;
                case ("bindTo"):
                    if (!self::isset($lOptions->getBindTo()))
                        $lOptions->setBindTo($rOptions->getBindTo());
                    break;
                case ("userData"):
                    if (!self::isset($lOptions->getUserData()))
                        $lOptions->setUserData($rOptions->getUserData());
                    break;
                case ("maxRedirects"):
                    if (!self::isset($lOptions->getMaxRedirects()))
                        $lOptions->setMaxRedirects($rOptions->getMaxRedirects());
                    break;
                case ("httpVersion"):
                    if (!self::isset($lOptions->getHttpVersion()))
                        $lOptions->setHttpVersion($rOptions->getHttpVersion());
                    break;
                case ("baseUri"):
                    if (!self::isset($lOptions->getBaseUri()))
                        $lOptions->setBaseUri($rOptions->getBaseUri());
                    break;
                case ("buffer"):
                    if (!self::isset($lOptions->getBuffer()))
                        $lOptions->setBuffer($rOptions->getBuffer());
                    break;
                case ("onProgress"):
                    if (!self::isset($lOptions->getOnProgress()))
                        $lOptions->setOnProgress($rOptions->getOnProgress());
                    break;
                case ("resolve"):
                    if (!self::isset($lOptions->getResolve()))
                        $lOptions->setResolve($rOptions->getResolve());
                    break;
                case ("json"):
                    if (!self::isset($lOptions->getJson()))
                        $lOptions->setJson($rOptions->getJson());
                    break;
                case ("body"):
                    if (!self::isset($lOptions->getBody()))
                        $lOptions->setBody($rOptions->getBody());
                    break;
            }
        }
        return $lOptions;
    }

    /** @param mixed $option */
    public static function isset($option): bool
    {
        if (is_array($option)){
            #ifndef KPHP
            if (is_array(reset($option)))
            {
                return ($option !== [[]]);
            }
            else return ($option !== []);
            #endif

            if (is_array(array_first_value($option))){
                return ($option !== [[]]);
            }
            else return ($option !== []);
        }
        elseif (is_string($option)){
            return $option !== '';
        }
        else return ($option !== null);
    }

    public function printOptions()
    {
        foreach (self::$fields as $field)
        {
            // если левый "массив" опций не установлен, используй правый
            switch ($field){
                case ("authBasic"):
                    var_dump($this->getAuthBasic());
                    break;
                case ("authBearer"):
                    var_dump($this->getAuthBearer());
                    break;
                case ("query"):
                    var_dump($this->getQuery());
                    break;
                case ("headers"):
                    var_dump($this->getHeaders());
                    break;
                case ("normalizedHeaders"):
                    var_dump($this->getNormalizedHeaders());
                    break;
                case ("proxy"):
                    var_dump($this->getProxy());
                    break;
                case ("noProxy"):
                    var_dump($this->getNoProxy());
                    break;
                case ("timeOut"):
                    var_dump($this->getTimeOut());
                    break;
                case ("maxDuration"):
                    var_dump($this->getMaxDuration());
                    break;
                case ("bindTo"):
                    var_dump($this->getBindTo());
                    break;
                case ("userData"):
                    var_dump($this->getUserData());
                    break;
                case ("maxRedirects"):
                    var_dump($this->getMaxRedirects());
                    break;
                case ("httpVersion"):
                    var_dump($this->getHttpVersion());
                    break;
                case ("baseUri"):
                    var_dump($this->getBaseUri());
                    break;
                case ("buffer"):
                    var_dump($this->getBuffer());
                    break;
                case ("onProgress"):
                    var_dump($this->getOnProgress());
                    break;
                case ("resolve"):
                    var_dump($this->getResolve());
                    break;
                case ("json"):
                    var_dump($this->getJson());
                    break;
                case ("body"):
                    var_dump($this->getBody());
                    break;
            }
        }
    }
}