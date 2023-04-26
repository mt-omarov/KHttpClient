<?php

namespace Kaa\HttpClient\Components\Exception;
use Kaa\HttpClient\Contracts\Exception\TransportExceptionInterface;

class TransportException extends \RuntimeException implements TransportExceptionInterface
{
}