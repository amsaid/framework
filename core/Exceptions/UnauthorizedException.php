<?php

namespace Core\Exceptions;

class UnauthorizedException extends HttpException
{
    public function __construct(string $message = "Unauthorized", \Throwable $previous = null)
    {
        parent::__construct($message, 401, $previous);
    }
}
