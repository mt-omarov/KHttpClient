<?php

namespace Kaa\HttpClient\Components;

class HandleActivity
{
    private ?string $message;
    private ?\Exception $error;
    private ?ErrorChunk $errorChunk;

    public function __construct()
    {
    }

    public function setMessage(string $message): self
    {
        $this->reset();
        $this->message = $message;
        return $this;
    }

    public function setError(\Exception $error): self
    {
        $this->reset();
        $this->error = $error;
        return $this;
    }

    public function setErrorChunk(ErrorChunk $errorChunk): self
    {
        $this->reset();
        $this->errorChunk = $errorChunk;
        return $this;
    }

    public function reset()
    {
        $this->message = null;
        $this->error = null;
        $this->errorChunk = null ;
    }

    public function getActivityMessage(): string
    {
        return $this->message;
    }

    public function getActivityError(): \Exception
    {
        return $this->error;
    }

    public function getActivityChunkError(): ErrorChunk
    {
        return $this->errorChunk;
    }
}