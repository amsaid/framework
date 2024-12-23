<?php

namespace Core\Exceptions;

class NotFoundException extends HttpException
{
    public function __construct(string $message = 'Not Found', int $code = 404)
    {
        parent::__construct($message, $code);
    }
}
