<?php

namespace Kaa\HttpClient\Components;

class CurlHandle
{
    /** @var int $handle */
    protected $handle;
    public function __construct(?string $url = null, ?int $handle = null)
    {
        $handle ? $this->handle = $handle : $this->handle = curl_init($url);
    }

    /**
     * @return int
     */
    public function getHandle()
    {
        return $this->handle;
    }

    /**
     * @param int $option
     * @param mixed $value
     * @return bool
     */
    public function curlSetOpt(int $option, $value): bool
    {
        return curl_setopt($this->handle, $option, $value);
    }

    public function curlClose(): void
    {
        curl_close($this->handle);
    }

    public function getInfo(?int $option = null)
    {
        return curl_getinfo($this->handle, $option);
    }
}