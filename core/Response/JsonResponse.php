<?php

namespace Core\Response;

class JsonResponse
{
    private $data;
    private int $statusCode;
    private array $headers;

    public function __construct($data = null, int $statusCode = 200, array $headers = [])
    {
        $this->data = $data;
        $this->statusCode = $statusCode;
        $this->headers = array_merge([
            'Content-Type' => 'application/json'
        ], $headers);
    }

    public function send(): void
    {
        // Set status code
        http_response_code($this->statusCode);

        // Set headers
        foreach ($this->headers as $name => $value) {
            header("$name: $value");
        }

        // Send JSON response
        echo json_encode($this->data, JSON_PRETTY_PRINT);
    }

    public static function success($data = null, int $statusCode = 200): self
    {
        return new self([
            'status' => 'success',
            'data' => $data
        ], $statusCode);
    }

    public static function error(string $message, int $statusCode = 400, $errors = null): self
    {
        $response = [
            'status' => 'error',
            'message' => $message
        ];

        if ($errors !== null) {
            $response['errors'] = $errors;
        }

        return new self($response, $statusCode);
    }
}
