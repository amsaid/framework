<?php

namespace Core;

use Core\Exceptions\FrameworkException;
use Core\Exceptions\HttpException;
use Core\Exceptions\NotFoundException;
use Core\Application;
use Core\Request;
use Core\Response;

class ErrorHandler
{
    private bool $debug;

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
        $statusCode = $this->getStatusCode($exception);
        $response = Application::getInstance()->getResponse();
        
        $response->setStatusCode($statusCode);
        
        if ($this->isApiRequest()) {
            $this->handleApiException($exception, $response);
        } else {
            $content = $this->debug ? $this->renderDebugView($exception) : $this->renderBasicErrorPage($exception);
            $response->setContent($content);
        }
        
        $response->send();
        exit(1);
    }

    public function handleShutdown(): void
    {
        $error = error_get_last();
        if ($error !== null && in_array($error['type'], [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_PARSE])) {
            $this->handleError($error['type'], $error['message'], $error['file'], $error['line']);
        }
    }

    private function handleApiException(\Throwable $exception, Response $response): void
    {
        $response->setHeader('Content-Type', 'application/json');
        $data = [
            'error' => [
                'code' => $this->getStatusCode($exception),
                'message' => $this->getExceptionMessage($exception)
            ]
        ];

        if ($this->debug) {
            $data['error']['debug'] = [
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => $exception->getTrace()
            ];
        }

        $response->setContent(json_encode($data, JSON_PRETTY_PRINT));
    }

    private function renderDebugView(\Throwable $exception): string
    {
        $statusCode = $this->getStatusCode($exception);
        $message = $this->getExceptionMessage($exception);
        $file = $exception->getFile();
        $line = $exception->getLine();
        $trace = $exception->getTraceAsString();
        
        return <<<HTML
        <!DOCTYPE html>
        <html>
        <head>
            <title>Error {$statusCode}</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; padding: 2rem; }
                .error { background: #f8d7da; border: 1px solid #f5c6cb; padding: 1rem; border-radius: 4px; }
                .trace { background: #f8f9fa; padding: 1rem; border-radius: 4px; margin-top: 1rem; }
                pre { margin: 0; white-space: pre-wrap; }
            </style>
        </head>
        <body>
            <div class="error">
                <h1>Error {$statusCode}</h1>
                <p><strong>{$message}</strong></p>
                <p>in {$file} on line {$line}</p>
            </div>
            <div class="trace">
                <h2>Stack Trace:</h2>
                <pre>{$trace}</pre>
            </div>
        </body>
        </html>
        HTML;
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
            <link rel="preconnect" href="https://fonts.googleapis.com">
            <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
            <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
            <style>
                * {
                    margin: 0;
                    padding: 0;
                    box-sizing: border-box;
                }
                
                body {
                    font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
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
            <script>
                // Add smooth fade-in animation
                document.addEventListener('DOMContentLoaded', () => {
                    const container = document.querySelector('.error-container');
                    container.style.opacity = '0';
                    container.style.transform = 'translateY(20px)';
                    container.style.transition = 'all 0.3s ease';
                    
                    setTimeout(() => {
                        container.style.opacity = '1';
                        container.style.transform = 'translateY(0)';
                    }, 100);
                });
            </script>
        </body>
        </html>
        HTML;
    }

    private function getStatusCode(\Throwable $exception): int
    {
        if ($exception instanceof HttpException) {
            return $exception->getCode();
        }
        
        return 500;
    }

    private function getExceptionMessage(\Throwable $exception): string
    {
        if ($this->debug) {
            return $exception->getMessage();
        }

        $statusCode = $this->getStatusCode($exception);
        return match ($statusCode) {
            404 => 'Page not found.',
            403 => 'Access denied.',
            default => 'An error occurred. Please try again later.'
        };
    }

    private function isApiRequest(): bool
    {
        $request = Application::getInstance()->getRequest();
        $path = $request->getPath();
        $acceptHeader = $request->header('Accept');
        
        return str_starts_with($path, '/api/') || 
               (str_contains($acceptHeader ?? '', 'application/json') && !str_contains($acceptHeader ?? '', 'text/html'));
    }
}
