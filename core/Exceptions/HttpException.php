<?php

namespace Core\Exceptions;

class HttpException extends \Exception
{
    protected int $statusCode;

    public function __construct(string $message = "", int $statusCode = 500, \Throwable $previous = null)
    {
        parent::__construct($message, $statusCode, $previous);
        $this->statusCode = $statusCode;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getContext(): array
    {
        return [
            'message' => $this->getMessage(),
            'code' => $this->getCode(),
            'status' => $this->getStatusCode()
        ];
    }
}
