<?php

namespace Core;

use Core\Exceptions\HttpException;

class Route
{
    private string $method;
    private string $path;
    private $handler;
    private array $middleware = [];
    private array $parameters = [];
    private array $whereConditions = [];
    private ?string $name = null;
    private ?Router $router = null;

    public function __construct(string $method, string $path, array|callable $handler)
    {
        $this->method = strtoupper($method);
        $this->path = $path;
        $this->handler = $handler;
    }

    public function setRouter(Router $router): self
    {
        $this->router = $router;
        return $this;
    }

    public function middleware(string|array $middleware): self
    {
        $middleware = is_array($middleware) ? $middleware : [$middleware];
        $this->middleware = array_merge($this->middleware, $middleware);
        return $this;
    }

    public function name(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function where(string $parameter, string $pattern): self
    {
        $this->whereConditions[$parameter] = $pattern;
        return $this;
    }

    public function setParameters(array $parameters): void
    {
        $this->parameters = $parameters;
    }

    public function getParameters(): array
    {
        return $this->parameters;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getMiddleware(): array
    {
        return $this->middleware;
    }

    public function resolveMiddleware(array $aliases): void
    {
        $resolved = [];
        foreach ($this->middleware as $middleware) {
            $resolved[] = $aliases[$middleware] ?? $middleware;
        }
        $this->middleware = $resolved;
    }

    public function execute(Request $request = null): mixed
    {
        if ($request === null) {
            $request = Application::getInstance()->getRequest();
        }

        // Execute middleware chain
        return $this->runMiddleware($request, function() use ($request) {
            return $this->runHandler($request);
        });
    }

    private function runMiddleware(Request $request, callable $next)
    {
        if (empty($this->middleware)) {
            return $next();
        }

        $middleware = array_shift($this->middleware);
        if (is_string($middleware)) {
            if ($this->router) {
                $middleware = $this->router->getMiddleware($middleware);
            }

            if (!class_exists($middleware)) {
                throw new HttpException("Middleware class not found: {$middleware}", 500);
            }

            $middlewareInstance = new $middleware();
            return $middlewareInstance->handle($request, function($request) use ($next) {
                return $this->runMiddleware($request, $next);
            });
        }

        return $next();
    }

    private function runHandler(Request $request): mixed
    {
        try {
            if (is_array($this->handler)) {
                [$controller, $method] = $this->handler;
                
                if (is_string($controller)) {
                    if (!class_exists($controller)) {
                        throw new HttpException("Controller class not found: {$controller}", 500);
                    }
                    $controller = new $controller();
                }

                if (!method_exists($controller, $method)) {
                    throw new HttpException("Method not found: {$controller}::{$method}", 500);
                }
                
                return $controller->$method($request, ...$this->parameters);
            }
            
            if (!is_callable($this->handler)) {
                throw new HttpException("Invalid route handler", 500);
            }
            
            return ($this->handler)($request, ...$this->parameters);
        } catch (\Throwable $e) {
            if ($e instanceof HttpException) {
                throw $e;
            }
            throw new HttpException($e->getMessage(), 500, $e);
        }
    }
}
