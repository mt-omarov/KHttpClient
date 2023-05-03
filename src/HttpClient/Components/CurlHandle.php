<?php

namespace Kaa\HttpClient\Components;

class CurlHandle
{
    /** @var int $handle */
    protected $handle;
    public function __construct()
    {
        $this->handle = curl_init();
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
     * @param int $value
     * @return bool
     */
    public function curlSetopt(int $option, mixed $value): bool
    {
        return curl_setopt($this->handle, $option, $value);
    }

    public function curlClose(): void
    {
        curl_close($this->handle);
    }
}