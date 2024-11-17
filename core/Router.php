<?php

namespace Core;

use Core\Exceptions\NotFoundException;
use Core\Exceptions\HttpException;
use Closure;

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

    public function __construct()
    {
        $this->loadRoutes();
        $this->loadMiddlewares();
    }

    private function loadRoutes(): void
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

    private function normalizePath(string $path): string
    {
        // Combine prefix with path if exists
        if ($this->prefix) {
            $path = trim($this->prefix, '/') . '/' . trim($path, '/');
        }
        return '/' . trim($path, '/');
    }

    public function addRoute(string $method, string $path, $handler): Route
    {
        $path = $this->normalizePath($path);
        
        // Create a new Route instance
        $route = new Route($method, $path, $handler);
        $route->setRouter($this);

        // Apply middleware if any
        if (!empty($this->groupMiddleware)) {
            $route->middleware($this->groupMiddleware);
        }

        // Apply name if set
        if (!empty($this->name)) {
            $route->name($this->name);
            $this->name = ''; // Reset name
        }

        // Apply where conditions if any
        if (!empty($this->whereConditions)) {
            foreach ($this->whereConditions as $param => $pattern) {
                $route->where($param, $pattern);
            }
            $this->whereConditions = []; // Reset conditions
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

    public function match(string $method, string $path): Route
    {
        $method = strtoupper($method);
        $path = $this->normalizePath($path);

        // Check for exact match
        if (isset($this->routes[$method][$path])) {
            return $this->prepareRoute($this->routes[$method][$path]);
        }

        // Check for pattern matches
        if (isset($this->routes[$method])) {
            foreach ($this->routes[$method] as $routePath => $route) {
                if ($this->matchDynamicRoute($routePath, $path, $params)) {
                    $route->setParameters($params);
                    return $this->prepareRoute($route);
                }
            }
        }

        throw new HttpException("Route not found: {$method} {$path}", 404);
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
}
