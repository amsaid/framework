<?php

namespace Core;

class Response
{
    private string $content = '';
    private int $statusCode = 200;
    private array $headers = [];
    private bool $contentTypeSet = false;

    public function setContent(string $content): self
    {
        $this->content = $content;
        
        // Auto-detect and set Content-Type if not already set
        if (!$this->contentTypeSet && !empty($content)) {
            if (str_starts_with(trim($content), '<!DOCTYPE html>') || 
                str_starts_with(trim($content), '<html') ||
                str_contains($content, '</body>')) {
                $this->setHeader('Content-Type', 'text/html; charset=UTF-8');
            } elseif ($this->isJson($content)) {
                $this->setHeader('Content-Type', 'application/json; charset=UTF-8');
            } else {
                $this->setHeader('Content-Type', 'text/plain; charset=UTF-8');
            }
        }
        
        return $this;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function setStatusCode(int $code): self
    {
        $this->statusCode = $code;
        return $this;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function setHeader(string $name, string $value): self
    {
        // Normalize header name
        $name = str_replace(' ', '-', ucwords(str_replace('-', ' ', strtolower($name))));
        
        if (strtolower($name) === 'content-type') {
            $this->contentTypeSet = true;
        }
        
        $this->headers[$name] = $value;
        return $this;
    }

    public function getHeader(string $name): ?string
    {
        // Normalize header name for case-insensitive lookup
        $name = str_replace(' ', '-', ucwords(str_replace('-', ' ', strtolower($name))));
        return $this->headers[$name] ?? null;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function hasHeader(string $name): bool
    {
        // Normalize header name for case-insensitive lookup
        $name = str_replace(' ', '-', ucwords(str_replace('-', ' ', strtolower($name))));
        return isset($this->headers[$name]);
    }

    public function removeHeader(string $name): self
    {
        // Normalize header name for case-insensitive removal
        $name = str_replace(' ', '-', ucwords(str_replace('-', ' ', strtolower($name))));
        
        if (strtolower($name) === 'content-type') {
            $this->contentTypeSet = false;
        }
        
        unset($this->headers[$name]);
        return $this;
    }

    public function send(): void
    {
        if (!headers_sent()) {
            // Set status code
            http_response_code($this->statusCode);
            
            // Ensure Content-Type is set
            if (!$this->contentTypeSet) {
                $this->setContent($this->content); // This will auto-detect and set Content-Type
            }
            
            // Send headers
            foreach ($this->headers as $name => $value) {
                header("$name: $value");
            }
        }

        echo $this->content;
    }

    private function isJson(string $string): bool
    {
        if (empty($string)) {
            return false;
        }
        
        $firstChar = $string[0];
        $lastChar = substr($string, -1);
        
        // Quick check for JSON-like structure
        if (($firstChar === '{' && $lastChar === '}') || 
            ($firstChar === '[' && $lastChar === ']')) {
            json_decode($string);
            return json_last_error() === JSON_ERROR_NONE;
        }
        
        return false;
    }
}
