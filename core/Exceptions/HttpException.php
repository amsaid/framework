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
}

class NotFoundException extends HttpException
{
    public function __construct(string $message = "Not Found", \Throwable $previous = null)
    {
        parent::__construct($message, 404, $previous);
    }
}

class UnauthorizedException extends HttpException
{
    public function __construct(string $message = "Unauthorized", \Throwable $previous = null)
    {
        parent::__construct($message, 401, $previous);
    }
}

class ForbiddenException extends HttpException
{
    public function __construct(string $message = "Forbidden", \Throwable $previous = null)
    {
        parent::__construct($message, 403, $previous);
    }
}

class ValidationException extends HttpException
{
    private array $errors;

    public function __construct(array $errors, string $message = "Validation Failed", \Throwable $previous = null)
    {
        $this->errors = $errors;
        parent::__construct($message, 422, $previous);
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function getContext(): array
    {
        return array_merge(parent::getContext(), [
            'errors' => $this->errors
        ]);
    }
}
