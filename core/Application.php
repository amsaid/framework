<?php

namespace Core;

use Core\Debug\Debug;
use Core\Environment;
use Core\Container;
use Core\Router;
use Core\Request;
use Core\Response;
use Core\ErrorHandler;
use Core\Config\Config;
use RuntimeException;

class Application
{
    private static ?self $instance = null;
    private Container $container;
    private Router $router;
    private bool $debugMode = false;
    private Request $request;
    private Response $response;
    private ErrorHandler $errorHandler;

    private function __construct()
    {
        try {
            // Initialize container
            $this->container = new Container();
            $this->registerBaseBindings();
            
            // Load environment variables
            Environment::load();
            
            // Load configuration
            Config::load();
            
            // Set debug mode from config or environment
            $this->debugMode = Config::get('app.debug', Environment::get('APP_DEBUG', false));
            
            // Initialize error handler first
            $this->errorHandler = new ErrorHandler($this->debugMode);
            
            if ($this->debugMode) {
                Debug::init();
                Debug::addTimelinePoint('Environment Loaded');
            }
            
            // Resolve core services
            $this->request = $this->container->make(Request::class);
            $this->response = $this->container->make(Response::class);
            $this->router = $this->container->make(Router::class);
            
            // Set container in router
            $this->router->setContainer($this->container);
            
            // Load routes
            $this->loadRoutes();
            
            if ($this->debugMode) {
                Debug::addTimelinePoint('Application Initialized');
            }
        } catch (\Throwable $e) {
            if (isset($this->errorHandler)) {
                $this->errorHandler->handleException($e);
            } else {
                throw $e;
            }
        }
    }

    /**
     * Register the basic bindings into the container.
     */
    protected function registerBaseBindings(): void
    {
        // Register the container as a singleton
        $this->container->instance(Container::class, $this->container);
        
        // Register the application instance
        $this->container->instance(self::class, $this);
        
        // Register core services as singletons
        $this->container->singleton(Router::class);
        $this->container->singleton(Request::class);
        $this->container->singleton(Response::class);
        $this->container->singleton(ErrorHandler::class);
        
        if ($this->debugMode) {
            Debug::addTimelinePoint('Base Bindings Registered');
        }
    }

    private function loadRoutes(): void
    {
        $routesPath = Config::get('app.routes_path', dirname(__DIR__) . '/routes');
        
        // Load web routes
        $webRoutesFile = $routesPath . '/web.php';
        if (file_exists($webRoutesFile)) {
            $this->router->loadWebRoutes();
        }
        
        // Load API routes
        $apiRoutesFile = $routesPath . '/api.php';
        if (file_exists($apiRoutesFile)) {
            $this->router->loadApiRoutes();
        }
        
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

    public function run(): void
    {
        try {
            // Handle the request
            $response = $this->router->dispatch($this->request);
            
            // Convert string responses to Response objects
            if (is_string($response)) {
                $this->response->setContent($response);
                $response = $this->response;
            } elseif ($response instanceof Response) {
                $this->response = $response;
            }
            
            // Add debug bar for HTML responses in debug mode
            if ($this->debugMode) {
                $contentType = $this->response->getHeader('Content-Type');
                if ((!$contentType || str_contains($contentType, 'text/html')) && !$this->request->isAjax()) {
                    $content = $this->response->getContent();
                    
                    // If content doesn't have </body>, add it
                    if (!str_contains($content, '</body>')) {
                        if (!str_contains($content, '</html>')) {
                            $content .= "\n</body>\n</html>";
                        } else {
                            $content = str_replace('</html>', "</body>\n</html>", $content);
                        }
                    }
                    
                    // Add debug bar before </body>
                    $debugBar = Debug::renderDebugBar();
                    $content = str_replace('</body>', $debugBar . "\n</body>", $content);
                    
                    // Ensure we have proper HTML structure
                    if (!str_contains($content, '<!DOCTYPE html>')) {
                        $content = "<!DOCTYPE html>\n<html>\n<head>\n<meta charset=\"UTF-8\">\n</head>\n<body>\n" . $content;
                    }
                    
                    $this->response->setContent($content);
                }
                Debug::addTimelinePoint('Debug Bar Added');
            }
            
            // Send the response
            $this->response->send();
            
            if ($this->debugMode) {
                Debug::addTimelinePoint('Response Sent');
            }
        } catch (\Throwable $e) {
            $this->errorHandler->handleException($e);
        }
    }

    public function getContainer(): Container
    {
        return $this->container;
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

    public function isDebugMode(): bool
    {
        return $this->debugMode;
    }
}
