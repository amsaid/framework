<?php

namespace Core;

use Closure;
use Core\Exceptions\HttpException;

class Route
{
    private string $method;
    private string $path;
    private $handler;
    private array $middleware = [];
    private ?Router $router = null;
    private string $name = '';
    private array $parameters = [];
    private array $whereConditions = [];

    public function __construct(string $method, string $path, $handler)
    {
        $this->method = strtoupper($method);
        $this->path = $path;
        $this->handler = $handler;
    }

    public function middleware(array|string $middleware): self
    {
        if (is_string($middleware)) {
            $middleware = [$middleware];
        }

        $this->middleware = array_merge($this->middleware, $middleware);
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

    public function matches(string $method, string $path): bool
    {
        if ($this->method !== $method) {
            return false;
        }

        $pattern = $this->path;

        // Replace route parameters with regex patterns
        foreach ($this->whereConditions as $param => $condition) {
            $pattern = str_replace("{{$param}}", "($condition)", $pattern);
        }

        // Replace remaining parameters with default pattern
        $pattern = preg_replace('/\{([^}]+)\}/', '([^/]+)', $pattern);
        $pattern = "#^{$pattern}$#";

        if (!preg_match($pattern, $path, $matches)) {
            return false;
        }

        // Extract parameter values
        array_shift($matches); // Remove the full match
        $paramNames = $this->getParameterNames();
        $this->parameters = array_combine($paramNames, $matches);

        return true;
    }

    private function getParameterNames(): array
    {
        preg_match_all('/\{([^}]+)\}/', $this->path, $matches);
        return $matches[1];
    }

    public function execute(Request $request): mixed
    {
        if (!$this->router) {
            throw new \RuntimeException("Router not set in Route");
        }

        // Apply middleware
        foreach ($this->middleware as $middleware) {
            $middlewareClass = $this->router->getMiddleware($middleware);
            $middlewareInstance = new $middlewareClass();
            $response = $middlewareInstance->handle($request, fn() => null);
            
            if ($response !== null) {
                return $response;
            }
        }

        // Execute handler with parameters
        return $this->router->executeHandler($this->handler, $this->parameters);
    }

    public function setRouter(Router $router): self
    {
        $this->router = $router;
        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getParameters(): array
    {
        return $this->parameters;
    }
}
