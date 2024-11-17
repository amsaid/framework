<?php

namespace Core;

use Core\Debug\Debug;
use Core\Environment;

class Application
{
    private static ?self $instance = null;
    private Router $router;
    private bool $debugMode = false;
    private array $config;
    private Request $request;
    private Response $response;
    private ErrorHandler $errorHandler;

    private function __construct()
    {
        // Load environment variables
        Environment::load();
        
        $this->loadConfig();
        $this->debugMode = Environment::get('APP_DEBUG', false);
        
        if ($this->debugMode) {
            Debug::init();
        }
        
        $this->errorHandler = new ErrorHandler($this->debugMode);
        $this->request = new Request();
        $this->response = new Response();
        $this->router = new Router();
        
        // Load routes
        $this->loadRoutes();
        
        if ($this->debugMode) {
            Debug::addTimelinePoint('Application Initialized');
        }
    }

    private function loadConfig(): void
    {
        $configPath = dirname(__DIR__) . '/config/app.php';
        $this->config = file_exists($configPath) ? require $configPath : [];
        
        if ($this->debugMode) {
            Debug::addTimelinePoint('Config Loaded');
        }
    }

    private function loadRoutes(): void
    {
        $router = $this->router;
        
        // Load web routes
        $webRoutes = require dirname(__DIR__) . '/routes/web.php';
        $webRoutes($router);
        
        // Load API routes
        $apiRoutes = require dirname(__DIR__) . '/routes/api.php';
        $apiRoutes($router);
        
        if ($this->debugMode) {
            Debug::addTimelinePoint('Routes Loaded');
        }
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getRouter(): Router
    {
        return $this->router;
    }

    public function getRequest(): Request
    {
        return $this->request;
    }

    public function getResponse(): Response
    {
        return $this->response;
    }

    public function getConfig(string $key = null, $default = null)
    {
        if ($key === null) {
            return $this->config;
        }

        return $this->config[$key] ?? $default;
    }

    public function isDebugMode(): bool
    {
        return $this->debugMode;
    }

    public function run(): void
    {
        try {
            if ($this->debugMode) {
                Debug::addTimelinePoint('Request Processing Start');
            }

            // Get the matching route for the current request
            $route = $this->router->match(
                $this->request->getMethod(),
                $this->request->getPath()
            );

            // Execute the route and get the response
            $response = $route->execute($this->request);
            
            if ($this->debugMode) {
                Debug::addTimelinePoint('Request Processing End');
                Debug::addMemoryPoint('Peak Memory Usage');
                
                // If it's an HTML response, inject the debug bar
                if (is_string($response) && str_contains($response, '</body>')) {
                    $debugBar = Debug::renderDebugBar();
                    $response = str_replace('</body>', $debugBar . '</body>', $response);
                }
            }

            // Set response content based on type
            if (is_string($response)) {
                $this->response->setContent($response);
            } elseif (is_array($response) || is_object($response)) {
                $this->response->setHeader('Content-Type', 'application/json');
                $this->response->setContent(json_encode($response));
            }

            $this->response->send();

        } catch (\Exception $e) {
            $this->errorHandler->handleException($e);
        }
    }
}
