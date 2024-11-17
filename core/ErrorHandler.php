<?php

namespace Core;

use Core\Exceptions\FrameworkException;
use Core\Exceptions\HttpException;
use Core\Exceptions\NotFoundException;
use Core\Application;
use Core\Request;
use Core\Response;
use Core\Debug\Debug;
use Core\Config\Config;

class ErrorHandler
{
    private bool $debug;
    private ?Response $response = null;

    public function __construct(bool $debug = false)
    {
        $this->debug = $debug;
        $this->register();
    }

    public function register(): void
    {
        error_reporting(E_ALL);
        set_error_handler([$this, 'handleError']);
        set_exception_handler([$this, 'handleException']);
        register_shutdown_function([$this, 'handleShutdown']);
    }

    public function handleError(int $level, string $message, string $file = '', int $line = 0): void
    {
        if (error_reporting() & $level) {
            throw new \ErrorException($message, 0, $level, $file, $line);
        }
    }

    public function handleException(\Throwable $exception): void
    {
        try {
            if ($this->debug) {
                Debug::addTimelinePoint('Exception Caught: ' . get_class($exception));
            }

            $statusCode = $this->getStatusCode($exception);
            
            // Get or create response object
            try {
                $this->response = Application::getInstance()->getResponse();
            } catch (\Throwable $e) {
                $this->response = new Response();
            }
            
            $this->response->setStatusCode($statusCode);
            
            if ($this->isApiRequest()) {
                $this->handleApiException($exception);
            } else {
                $content = $this->debug ? $this->renderDebugView($exception) : $this->renderBasicErrorPage($exception);
                $this->response->setContent($content);
            }
            
            // Add debug bar in debug mode for HTML responses
            if ($this->debug && 
                $this->response->getHeader('Content-Type') === 'text/html' && 
                !$this->isApiRequest()) {
                $content = $this->response->getContent();
                if (str_contains($content, '</body>')) {
                    $debugBar = Debug::renderDebugBar();
                    $content = str_replace('</body>', $debugBar . '</body>', $content);
                    $this->response->setContent($content);
                }
            }

            $this->response->send();
        } catch (\Throwable $e) {
            // Fallback error handling if something goes wrong in the error handler
            http_response_code(500);
            if ($this->debug) {
                echo '<h1>Critical Error in Error Handler</h1>';
                echo '<pre>' . htmlspecialchars($e->getMessage() . "\n" . $e->getTraceAsString()) . '</pre>';
            } else {
                echo '<h1>500 Internal Server Error</h1>';
                echo '<p>An unexpected error occurred. Please try again later.</p>';
            }
        }
        
        exit(1);
    }

    public function handleShutdown(): void
    {
        $error = error_get_last();
        if ($error !== null && in_array($error['type'], [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_PARSE])) {
            $this->handleError($error['type'], $error['message'], $error['file'], $error['line']);
        }
    }

    private function handleApiException(\Throwable $exception): void
    {
        $this->response->setHeader('Content-Type', 'application/json');
        $data = [
            'error' => [
                'code' => $this->getStatusCode($exception),
                'message' => $this->getExceptionMessage($exception)
            ]
        ];

        if ($this->debug) {
            $data['error']['debug'] = [
                'exception' => get_class($exception),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => $exception->getTrace()
            ];
        }

        $this->response->setContent(json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    private function renderDebugView(\Throwable $exception): string
    {
        $statusCode = $this->getStatusCode($exception);
        $exceptionClass = get_class($exception);
        $message = $this->getExceptionMessage($exception);
        $file = $exception->getFile();
        $line = $exception->getLine();
        $trace = $exception->getTraceAsString();
        
        // Get file preview
        $filePreview = $this->getFilePreview($file, $line);
        
        return <<<HTML
        <!DOCTYPE html>
        <html>
        <head>
            <title>Error {$statusCode}</title>
            <style>
                :root {
                    --bg-color: #f8f9fa;
                    --text-color: #212529;
                    --error-bg: #f8d7da;
                    --error-border: #f5c6cb;
                    --code-bg: #272822;
                    --code-text: #f8f8f2;
                }
                
                body {
                    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
                    line-height: 1.6;
                    color: var(--text-color);
                    background: var(--bg-color);
                    padding: 2rem;
                    margin: 0;
                }
                
                .container {
                    max-width: 1200px;
                    margin: 0 auto;
                }
                
                .error-header {
                    background: var(--error-bg);
                    border: 1px solid var(--error-border);
                    padding: 1.5rem;
                    border-radius: 4px;
                    margin-bottom: 2rem;
                }
                
                .error-title {
                    margin: 0 0 1rem 0;
                    font-size: 1.5rem;
                    font-weight: 600;
                }
                
                .error-details {
                    margin: 0;
                    font-family: monospace;
                }
                
                .section {
                    background: white;
                    border-radius: 4px;
                    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
                    margin-bottom: 2rem;
                    overflow: hidden;
                }
                
                .section-header {
                    background: #e9ecef;
                    padding: 1rem 1.5rem;
                    font-weight: 600;
                    border-bottom: 1px solid #dee2e6;
                }
                
                .section-content {
                    padding: 1.5rem;
                }
                
                .code-preview {
                    background: var(--code-bg);
                    color: var(--code-text);
                    padding: 1rem;
                    border-radius: 4px;
                    overflow-x: auto;
                    font-family: 'SFMono-Regular', Consolas, 'Liberation Mono', Menlo, Courier, monospace;
                }
                
                .line-number {
                    color: #75715e;
                    padding-right: 1rem;
                    user-select: none;
                }
                
                .error-line {
                    background: rgba(255,0,0,0.2);
                }
                
                .stack-trace {
                    font-family: monospace;
                    white-space: pre-wrap;
                    word-break: break-all;
                }
                
                @media (max-width: 768px) {
                    body {
                        padding: 1rem;
                    }
                    
                    .error-header {
                        padding: 1rem;
                    }
                }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="error-header">
                    <h1 class="error-title">Error {$statusCode} - {$exceptionClass}</h1>
                    <p class="error-details">{$message}</p>
                    <p class="error-details">in {$file} on line {$line}</p>
                </div>
                
                <div class="section">
                    <div class="section-header">Code Preview</div>
                    <div class="section-content">
                        <div class="code-preview">{$filePreview}</div>
                    </div>
                </div>
                
                <div class="section">
                    <div class="section-header">Stack Trace</div>
                    <div class="section-content">
                        <div class="stack-trace">{$trace}</div>
                    </div>
                </div>
            </div>
        </body>
        </html>
        HTML;
    }

    private function getFilePreview(string $file, int $line, int $context = 10): string
    {
        if (!file_exists($file)) {
            return 'File not found';
        }

        try {
            $lines = file($file);
            $start = max(0, $line - $context - 1);
            $end = min(count($lines), $line + $context);
            
            $output = '';
            for ($i = $start; $i < $end; $i++) {
                $currentLine = $i + 1;
                $lineContent = htmlspecialchars($lines[$i]);
                $class = $currentLine === $line ? 'error-line' : '';
                $output .= sprintf(
                    '<div class="%s"><span class="line-number">%d</span>%s</div>',
                    $class,
                    $currentLine,
                    $lineContent
                );
            }
            
            return $output;
        } catch (\Throwable $e) {
            return 'Could not read file contents';
        }
    }

    private function renderBasicErrorPage(\Throwable $exception): string
    {
        $statusCode = $this->getStatusCode($exception);
        $message = $this->getExceptionMessage($exception);
        
        return <<<HTML
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Error {$statusCode}</title>
            <style>
                * {
                    margin: 0;
                    padding: 0;
                    box-sizing: border-box;
                }
                
                body {
                    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
                    line-height: 1.6;
                    min-height: 100vh;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    background: linear-gradient(135deg, #f5f7fa 0%, #e4e7eb 100%);
                    padding: 1rem;
                }
                
                .error-container {
                    background: white;
                    padding: 2.5rem;
                    border-radius: 1rem;
                    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.05);
                    width: 100%;
                    max-width: 600px;
                    text-align: center;
                    opacity: 0;
                    transform: translateY(20px);
                    animation: fadeIn 0.3s ease forwards;
                }
                
                @keyframes fadeIn {
                    to {
                        opacity: 1;
                        transform: translateY(0);
                    }
                }
                
                .error-code {
                    font-size: 6rem;
                    font-weight: 600;
                    line-height: 1;
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    -webkit-background-clip: text;
                    -webkit-text-fill-color: transparent;
                    margin-bottom: 1rem;
                }
                
                .error-title {
                    font-size: 1.5rem;
                    color: #1a202c;
                    margin-bottom: 1rem;
                    font-weight: 500;
                }
                
                .error-message {
                    color: #4a5568;
                    margin-bottom: 2rem;
                }
                
                .back-button {
                    display: inline-block;
                    padding: 0.75rem 1.5rem;
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    color: white;
                    text-decoration: none;
                    border-radius: 0.5rem;
                    font-weight: 500;
                    transition: all 0.2s ease;
                    border: none;
                    cursor: pointer;
                }
                
                .back-button:hover {
                    transform: translateY(-2px);
                    box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
                }
                
                @media (max-width: 640px) {
                    .error-container {
                        padding: 2rem;
                    }
                    
                    .error-code {
                        font-size: 4rem;
                    }
                    
                    .error-title {
                        font-size: 1.25rem;
                    }
                }
            </style>
        </head>
        <body>
            <div class="error-container">
                <div class="error-code">{$statusCode}</div>
                <h1 class="error-title">Oops! Something went wrong</h1>
                <p class="error-message">{$message}</p>
                <button onclick="window.history.back()" class="back-button">Go Back</button>
            </div>
        </body>
        </html>
        HTML;
    }

    private function getStatusCode(\Throwable $exception): int
    {
        if ($exception instanceof HttpException) {
            return $exception->getCode();
        }
        
        if ($exception instanceof NotFoundException) {
            return 404;
        }
        
        return $exception->getCode() ?: 500;
    }

    private function getExceptionMessage(\Throwable $exception): string
    {
        if ($this->debug) {
            return $exception->getMessage();
        }

        $statusCode = $this->getStatusCode($exception);
        return match ($statusCode) {
            404 => 'The page you are looking for could not be found.',
            403 => 'You do not have permission to access this resource.',
            429 => 'Too many requests. Please try again later.',
            503 => 'Service temporarily unavailable. Please try again later.',
            default => Config::get('app.error_message', 'An error occurred. Please try again later.')
        };
    }

    private function isApiRequest(): bool
    {
        try {
            $request = Application::getInstance()->getRequest();
            return $request->isAjax() || 
                   str_starts_with($request->getPath(), '/api/') || 
                   $request->header('Accept') === 'application/json';
        } catch (\Throwable $e) {
            // Fallback if Request object is not available
            return isset($_SERVER['HTTP_ACCEPT']) && 
                   strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false;
        }
    }
}
