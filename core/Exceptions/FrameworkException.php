<?php

namespace Core\Exceptions;

class FrameworkException extends \Exception
{
    protected $statusCode = 500;
    
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }
    
    public function getContext(): array
    {
        return [
            'message' => $this->getMessage(),
            'code' => $this->getCode(),
            'file' => $this->getFile(),
            'line' => $this->getLine(),
            'trace' => $this->getTraceAsString()
        ];
    }
}
