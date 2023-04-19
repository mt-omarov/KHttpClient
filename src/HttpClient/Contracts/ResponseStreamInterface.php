<?php

namespace kCurl\HttpClient\Contracts;

interface ResponseStreamInterface extends \Iterator
{
    public function key(): ResponseInterface;
//    public function current(): ChunkInterface;
}
