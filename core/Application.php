<?php

namespace Core;

use Core\Debug\Debug;
use Core\Environment;
use Core\Container;
use Core\Router;
use Core\Request;
use Core\Response;
use Core\ErrorHandler;

class Application
{
    private static ?self $instance = null;
    private Container $container;
    private Router $router;
    private bool $debugMode = false;
    private array $config;
    private Request $request;
    private Response $response;
    private ErrorHandler $errorHandler;

    private function __construct()
    {
        // Initialize container
        $this->container = new Container();
        $this->registerBaseBindings();
        
        // Load environment variables
        Environment::load();
        
        $this->loadConfig();
        $this->debugMode = Environment::get('APP_DEBUG', false);
        
        if ($this->debugMode) {
            Debug::init();
        }
        
        // Resolve core services
        $this->errorHandler = $this->container->make(ErrorHandler::class, ['debugMode' => $this->debugMode]);
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
        // Load web routes
        $this->router->loadWebRoutes();
        
        // Load API routes
        $this->router->loadApiRoutes();
        
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

    public function getConfig(string $key = null, $default = null)
    {
        if ($key === null) {
            return $this->config;
        }

        return $this->config[$key] ?? $default;
    }

    public function run(): void
    {
        try {
            $response = $this->router->dispatch($this->request);
            if (is_string($response)) {
                $this->response->setContent($response);
            } elseif (is_array($response) || is_object($response)) {
                $this->response->setHeader('Content-Type', 'application/json');
                $this->response->setContent(json_encode($response));
            } elseif ($response instanceof Response) {
                $this->response = $response;
            }

            if ($this->debugMode && $this->response->getHeader('Content-Type') === 'text/html') {
                $content = $this->response->getContent();
                if (str_contains($content, '</body>')) {
                    $debugBar = Debug::renderDebugBar();
                    $content = str_replace('</body>', $debugBar . '</body>', $content);
                    $this->response->setContent($content);
                }
            }

            $this->response->send();
        } catch (\Exception $e) {
            $this->errorHandler->handleException($e);
        }
    }
}
