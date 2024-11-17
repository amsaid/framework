<?php

namespace Core;

use Core\Exceptions\NotFoundException;
use Core\Exceptions\HttpException;
use Closure;
use Core\Container;
use RuntimeException;
use Core\Route;
use Core\Request;

class Router
{
    private array $routes = [];
    private array $middlewares = [];
    private array $routeMiddlewares = [];
    private array $patterns = [
        ':any' => '[^/]+',
        ':num' => '[0-9]+',
        ':alpha' => '[a-zA-Z]+',
        ':alphanum' => '[a-zA-Z0-9]+',
        ':slug' => '[a-z0-9-]+',
        ':uuid' => '[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}'
    ];

    private ?string $prefix = null;
    private array $groupMiddleware = [];
    private string $name = '';
    private array $whereConditions = [];
    private ?Container $container = null;
    private bool $removeTrailingSlash = true;

    public function __construct(Container $container)
    {
        $this->container = $container;
        $this->loadMiddlewares();
    }

    public function setTrailingSlashBehavior(bool $remove): void
    {
        $this->removeTrailingSlash = $remove;
    }

    private function normalizePath(string $path): string
    {
        // Combine prefix with path if exists
        if ($this->prefix) {
            $path = trim($this->prefix, '/') . '/' . trim($path, '/');
        }
        return '/' . trim($path, '/');
    }

    private function normalizeUri(string $uri): string
    {
        // Remove multiple consecutive slashes
        $uri = preg_replace('#/+#', '/', $uri);
        
        // Handle root path
        if ($uri === '') {
            return '/';
        }

        // Remove trailing slash if configured to do so
        if ($this->removeTrailingSlash && strlen($uri) > 1) {
            $uri = rtrim($uri, '/');
        }

        // Ensure URI starts with /
        if (!str_starts_with($uri, '/')) {
            $uri = '/' . $uri;
        }

        return $uri;
    }

    public function loadWebRoutes(): void
    {
        $routesPath = dirname(__DIR__) . '/routes/web.php';
        if (file_exists($routesPath)) {
            $routes = require $routesPath;
            if (is_array($routes)) {
                foreach ($routes as $method => $methodRoutes) {
                    foreach ($methodRoutes as $path => $handler) {
                        $this->addRoute($method, $path, $handler);
                    }
                }
            }
            elseif (is_callable($routes)) {
                $routes($this);
            }
        }
    }

    public function loadApiRoutes(): void
    {
        $routesPath = dirname(__DIR__) . '/routes/api.php';
        if (file_exists($routesPath)) {
            $routes = require $routesPath;
            if (is_array($routes)) {
                foreach ($routes as $method => $methodRoutes) {
                    foreach ($methodRoutes as $path => $handler) {
                        $this->addRoute($method, $path, $handler);
                    }
                }
            }
            elseif (is_callable($routes)) {
                $routes($this);
            }
        }
    }

    private function loadMiddlewares(): void
    {
        $middlewaresPath = dirname(__DIR__) . '/routes/middleware.php';
        if (file_exists($middlewaresPath)) {
            $middlewareConfig = require $middlewaresPath;
            if (isset($middlewareConfig['global'])) {
                $this->middlewares = $middlewareConfig['global'];
            }
            if (isset($middlewareConfig['aliases'])) {
                $this->routeMiddlewares = $middlewareConfig['aliases'];
            }
        }
    }

    public function addRoute(string $method, string $path, $handler): Route
    {
        $path = $this->normalizePath($path);
        $method = strtoupper($method);

        // Create a new Route instance
        $route = new Route($method, $path, $handler);
        $route->setRouter($this);
        
        // Apply current middleware group if exists
        if (!empty($this->groupMiddleware)) {
            $route->middleware($this->groupMiddleware);
        }
        
        // Apply where conditions if exists
        foreach ($this->whereConditions as $param => $pattern) {
            $route->where($param, $pattern);
        }
        
        // Apply name prefix if exists
        if ($this->name) {
            $route->name($this->name . '.');
        }
        
        // Store the route
        if (!isset($this->routes[$method])) {
            $this->routes[$method] = [];
        }
        $this->routes[$method][$path] = $route;
        
        return $route;
    }

    public function get(string $path, $handler): Route
    {
        return $this->addRoute('GET', $path, $handler);
    }

    public function post(string $path, $handler): Route
    {
        return $this->addRoute('POST', $path, $handler);
    }

    public function put(string $path, $handler): Route
    {
        return $this->addRoute('PUT', $path, $handler);
    }

    public function patch(string $path, $handler): Route
    {
        return $this->addRoute('PATCH', $path, $handler);
    }

    public function delete(string $path, $handler): Route
    {
        return $this->addRoute('DELETE', $path, $handler);
    }

    public function prefix(string $prefix): self
    {
        $this->prefix = $prefix;
        return $this;
    }

    public function middleware(array|string $middleware): self
    {
        $middleware = (array)$middleware;
        $this->groupMiddleware = array_merge($this->groupMiddleware, $middleware);
        return $this;
    }

    public function name(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function where(string $param, string $pattern): self
    {
        $this->whereConditions[$param] = $pattern;
        return $this;
    }

    public function group(array $attributes, callable $callback): void
    {
        // Save current state
        $previousPrefix = $this->prefix;
        $previousMiddleware = $this->groupMiddleware;

        // Apply group attributes
        if (isset($attributes['prefix'])) {
            $this->prefix = $previousPrefix 
                ? trim($previousPrefix, '/') . '/' . trim($attributes['prefix'], '/')
                : trim($attributes['prefix'], '/');
        }

        if (isset($attributes['middleware'])) {
            $middleware = (array)$attributes['middleware'];
            $this->groupMiddleware = array_merge($this->groupMiddleware, $middleware);
        }

        // Execute the group definition
        $callback($this);

        // Restore previous state
        $this->prefix = $previousPrefix;
        $this->groupMiddleware = $previousMiddleware;
    }

    public function match(Request $request): ?Route
    {
        $method = $request->getMethod();
        $uri = $this->normalizeUri($request->getPath());

        // Check for exact match first
        if (isset($this->routes[$method][$uri])) {
            return $this->routes[$method][$uri];
        }

        // If trailing slash behavior is enabled and no exact match was found,
        // try the alternate version (with/without trailing slash)
        if ($this->removeTrailingSlash && strlen($uri) > 1) {
            $alternateUri = str_ends_with($uri, '/') 
                ? rtrim($uri, '/') 
                : $uri . '/';
            
            if (isset($this->routes[$method][$alternateUri])) {
                // Redirect to the normalized version
                header('Location: ' . $uri, true, 301);
                exit();
            }
        }

        // Check for pattern matches if no exact match found
        foreach ($this->routes[$method] ?? [] as $route) {
            if ($route->matches($method, $uri)) {
                return $route;
            }
        }

        throw new HttpException("Route not found: {$method} {$uri}", 404);
    }

    private function prepareRoute(Route $route): Route
    {
        // Apply global middleware
        foreach ($this->middlewares as $middleware) {
            $route->middleware($middleware);
        }

        return $route;
    }

    private function matchDynamicRoute(string $routePath, string $requestPath, &$params = []): bool
    {
        $routeParts = explode('/', trim($routePath, '/'));
        $requestParts = explode('/', trim($requestPath, '/'));

        if (count($routeParts) !== count($requestParts)) {
            return false;
        }

        $params = [];
        for ($i = 0; $i < count($routeParts); $i++) {
            if (strpos($routeParts[$i], ':') === 0) {
                // This is a parameter
                $paramName = substr($routeParts[$i], 1);
                $params[$paramName] = $requestParts[$i];
            } elseif ($routeParts[$i] !== $requestParts[$i]) {
                return false;
            }
        }

        return true;
    }

    public function getMiddleware(string $middleware): string
    {
        return $this->routeMiddlewares[$middleware] ?? $middleware;
    }

    public function dispatch(Request $request): mixed
    {
        if (!$this->container) {
            throw new RuntimeException("Container not set in Router");
        }

        $method = strtoupper($request->getMethod());
        $path = $request->getPath();

        // Check if we have any routes for this method
        if (!isset($this->routes[$method])) {
            throw new NotFoundException("No routes found for method {$method}");
        }

        // Try to match the route
        foreach ($this->routes[$method] as $route) {
            if ($route->matches($method, $path)) {
                return $route->execute($request);
            }
        }

        throw new NotFoundException("No route found for {$method} {$path}");
    }

    /**
     * Execute the route handler with dependency injection.
     *
     * @param string|array|Closure $handler
     * @param array $parameters
     * @return mixed
     */
    public function executeHandler($handler, array $parameters = [])
    {
        if (!$this->container) {
            throw new RuntimeException("Container not set in Router");
        }

        if ($handler instanceof Closure) {
            return $this->container->call($handler, $parameters);
        }

        // If handler is array [Controller::class, 'method']
        if (is_array($handler)) {
            $controller = $handler[0];
            $method = $handler[1];

            if (is_string($controller)) {
                $controller = $this->container->make($controller);
            }

            return $this->container->call([$controller, $method], $parameters);
        }

        // If handler is string "Controller@method"
        if (is_string($handler) && str_contains($handler, '@')) {
            [$controller, $method] = explode('@', $handler);
            $controller = $this->container->make($controller);
            return $this->container->call([$controller, $method], $parameters);
        }

        throw new RuntimeException("Invalid route handler");
    }

    /**
     * Set the container instance.
     *
     * @param Container $container
     * @return void
     */
    public function setContainer(Container $container): void
    {
        $this->container = $container;
    }
}
