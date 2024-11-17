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

    /**
     * Check if the request is an AJAX request
     */
    public function isAjax(): bool
    {
        return $this->header('X-Requested-With') === 'XMLHttpRequest' ||
               $this->wantsJson();
    }

    /**
     * Check if the request wants a JSON response
     */
    public function wantsJson(): bool
    {
        $accept = $this->header('Accept');
        return $accept && (
            str_contains($accept, '/json') || 
            str_contains($accept, '+json')
        );
    }

    /**
     * Check if the request expects JSON
     */
    public function expectsJson(): bool
    {
        return $this->wantsJson() || 
               $this->isJson();
    }

    /**
     * Check if the request has JSON content
     */
    public function isJson(): bool
    {
        $contentType = $this->header('Content-Type');
        return $contentType && (
            str_contains($contentType, '/json') || 
            str_contains($contentType, '+json')
        );
    }

    /**
     * Get the full URL of the request
     */
    public function getUrl(): string
    {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        
        return "{$protocol}://{$host}{$uri}";
    }

    /**
     * Get the base URL of the application
     */
    public function getBaseUrl(): string
    {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        
        return "{$protocol}://{$host}";
    }

    /**
     * Get the IP address of the request
     */
    public function getIp(): string
    {
        $headers = [
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR'
        ];

        foreach ($headers as $header) {
            if (isset($_SERVER[$header])) {
                foreach (explode(',', $_SERVER[$header]) as $ip) {
                    $ip = trim($ip);
                    if (filter_var($ip, FILTER_VALIDATE_IP)) {
                        return $ip;
                    }
                }
            }
        }

        return '0.0.0.0';
    }

    /**
     * Get the user agent string
     */
    public function getUserAgent(): string
    {
        return $_SERVER['HTTP_USER_AGENT'] ?? '';
    }

    /**
     * Check if the request is secure (HTTPS)
     */
    public function isSecure(): bool
    {
        return isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    }
}
