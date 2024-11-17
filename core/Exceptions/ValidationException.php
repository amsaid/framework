<?php

namespace Core\Exceptions;

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
