<?php

namespace Kaa\HttpClient\Components;

class CurlMultiHandle extends CurlHandle
{
    public function __construct()
    {
        $this->handle = curl_multi_init();
    }

    /**
     * @param int $option
     * @param int $value
     * @return bool
     */
    public function curlSetopt(int $option, mixed $value): bool
    {
        return curl_multi_setopt($this->handle, $option, $value);
    }

    /**
     * @param int $handle
     * @return int
     */
    public function removeMultiHandle($handle): int
    {
        return curl_multi_remove_handle($this->handle, $handle);
    }

    public function curlClose(): void
    {
        curl_multi_close($this->handle);
    }

    /**
     * @param int $active
     * @return int
     */
    public function curlMultiExec(&$active): int
    {
        return curl_multi_exec($this->handle, $active);
    }
}