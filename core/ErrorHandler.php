<?php

namespace Core;

use Core\Exceptions\FrameworkException;
use Core\Exceptions\HttpException;

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
            if ($this->debug) {
                $this->renderDebugView($exception);
            } else {
                $this->handleWebException($exception, $response);
            }
        }
    }

    private function handleApiException(\Throwable $exception, Response $response): void
    {
        $response->setHeader('Content-Type', 'application/json');
        
        $data = [
            'error' => true,
            'message' => $this->getExceptionMessage($exception),
            'code' => $exception->getCode()
        ];

        if ($this->debug) {
            $data['debug'] = $this->getDebugData($exception);
        }

        if ($exception instanceof FrameworkException) {
            $data = array_merge($data, $exception->getContext());
        }

        $response->setContent(json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        $response->send();
    }

    private function handleWebException(\Throwable $exception, Response $response): void
    {
        $statusCode = $this->getStatusCode($exception);
        $view = new View("errors/{$statusCode}", [
            'exception' => $exception,
            'debug' => $this->debug ? $this->getDebugData($exception) : null
        ]);
        
        try {
            $content = $view->render();
        } catch (\Exception $e) {
            // Fallback to basic error template if view not found
            $content = $this->renderBasicErrorPage($exception);
        }

        $response->setContent($content);
        $response->send();
    }

    private function renderDebugView(\Throwable $exception): void
    {
        $debugView = dirname(__DIR__) . '/app/Views/errors/debug.php';
        if (file_exists($debugView)) {
            include $debugView;
            return;
        }
        
        // Fallback if debug view doesn't exist
        echo '<h1>Error: ' . get_class($exception) . '</h1>';
        echo '<p>' . htmlspecialchars($exception->getMessage()) . '</p>';
        echo '<pre>' . htmlspecialchars($exception->getTraceAsString()) . '</pre>';
    }

    private function getStatusCode(\Throwable $exception): int
    {
        if ($exception instanceof HttpException) {
            return $exception->getStatusCode();
        }

        return 500;
    }

    private function getExceptionMessage(\Throwable $exception): string
    {
        return $this->debug ? $exception->getMessage() : $this->getPublicMessage($exception);
    }

    private function getPublicMessage(\Throwable $exception): string
    {
        $statusCode = $this->getStatusCode($exception);
        
        return match ($statusCode) {
            404 => 'The requested resource was not found.',
            403 => 'You do not have permission to access this resource.',
            401 => 'Authentication is required to access this resource.',
            422 => 'The submitted data was invalid.',
            default => 'An unexpected error occurred. Please try again later.'
        };
    }

    private function getDebugData(\Throwable $exception): array
    {
        return [
            'message' => $exception->getMessage(),
            'code' => $exception->getCode(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString()
        ];
    }

    private function isApiRequest(): bool
    {
        $acceptHeader = $_SERVER['HTTP_ACCEPT'] ?? '';
        $path = $_SERVER['REQUEST_URI'] ?? '';
        
        return str_contains($acceptHeader, 'application/json') 
            || str_starts_with($path, '/api/');
    }

    private function renderBasicErrorPage(\Throwable $exception): string
    {
        $statusCode = $this->getStatusCode($exception);
        $message = $this->getExceptionMessage($exception);
        
        return <<<HTML
        <!DOCTYPE html>
        <html>
        <head>
            <title>Error {$statusCode}</title>
            <style>
                body { 
                    font-family: Arial, sans-serif;
                    margin: 40px;
                    text-align: center;
                }
                .error-container {
                    max-width: 600px;
                    margin: 0 auto;
                }
                .error-code {
                    font-size: 72px;
                    color: #e74c3c;
                    margin: 0;
                }
                .error-message {
                    font-size: 24px;
                    color: #555;
                    margin: 20px 0;
                }
            </style>
        </head>
        <body>
            <div class="error-container">
                <h1 class="error-code">{$statusCode}</h1>
                <p class="error-message">{$message}</p>
            </div>
        </body>
        </html>
        HTML;
    }

    public function handleShutdown(): void
    {
        $error = error_get_last();
        
        if ($error !== null && ($error['type'] & (E_ERROR | E_PARSE | E_CORE_ERROR | E_COMPILE_ERROR))) {
            $this->handleError($error['type'], $error['message'], $error['file'], $error['line']);
        }
    }
}
