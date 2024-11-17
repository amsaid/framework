<?php

namespace Core;

class Request
{
    private array $headers;

    public function __construct()
    {
        $this->headers = $this->getAllHeaders();
    }

    private function getAllHeaders(): array
    {
        $headers = [];
        
        if (function_exists('getallheaders')) {
            $headers = getallheaders();
        } else {
            foreach ($_SERVER as $name => $value) {
                if (substr($name, 0, 5) === 'HTTP_') {
                    $name = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))));
                    $headers[$name] = $value;
                }
            }
        }
        
        return $headers;
    }

    public function header(string $name): ?string
    {
        // Try direct match first
        if (isset($this->headers[$name])) {
            return $this->headers[$name];
        }
        
        // Try case-insensitive match
        $name = strtolower($name);
        foreach ($this->headers as $key => $value) {
            if (strtolower($key) === $name) {
                return $value;
            }
        }
        
        return null;
    }

    public function json(): array
    {
        $contentType = $this->header('Content-Type');
        if ($contentType && str_contains(strtolower($contentType), 'application/json')) {
            $content = file_get_contents('php://input');
            return json_decode($content, true) ?? [];
        }
        return [];
    }

    public function getPath(): string
    {
        $path = $_SERVER['REQUEST_URI'] ?? '/';
        $position = strpos($path, '?');
        
        if ($position === false) {
            return $path;
        }
        
        return substr($path, 0, $position);
    }

    public function getMethod(): string
    {
        return strtoupper($_SERVER['REQUEST_METHOD']);
    }

    public function getBody(): array
    {
        $body = [];

        if ($this->getMethod() === 'GET') {
            foreach ($_GET as $key => $value) {
                $body[$key] = filter_input(INPUT_GET, $key, FILTER_SANITIZE_SPECIAL_CHARS);
            }
        }

        if ($this->getMethod() === 'POST') {
            foreach ($_POST as $key => $value) {
                $body[$key] = filter_input(INPUT_POST, $key, FILTER_SANITIZE_SPECIAL_CHARS);
            }
        }

        return $body;
    }
}
