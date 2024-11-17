<?php

namespace Core;

use Closure;
use ReflectionClass;
use ReflectionParameter;
use ReflectionMethod;
use RuntimeException;

class Container
{
    /**
     * The container's bindings.
     *
     * @var array
     */
    protected array $bindings = [];

    /**
     * The container's shared instances.
     *
     * @var array
     */
    protected array $instances = [];

    /**
     * Bind a type into the container.
     *
     * @param string $abstract
     * @param Closure|string|null $concrete
     * @param bool $shared
     * @return void
     */
    public function bind(string $abstract, $concrete = null, bool $shared = false): void
    {
        if (is_null($concrete)) {
            $concrete = $abstract;
        }

        if (!$concrete instanceof Closure) {
            if (!is_string($concrete)) {
                throw new RuntimeException("Invalid concrete type for [$abstract]");
            }

            $concrete = $this->getClosure($abstract, $concrete);
        }

        $this->bindings[$abstract] = compact('concrete', 'shared');
    }

    /**
     * Register a shared binding in the container.
     *
     * @param string $abstract
     * @param Closure|string|null $concrete
     * @return void
     */
    public function singleton(string $abstract, $concrete = null): void
    {
        $this->bind($abstract, $concrete, true);
    }

    /**
     * Register an existing instance as shared in the container.
     *
     * @param string $abstract
     * @param mixed $instance
     * @return mixed
     */
    public function instance(string $abstract, $instance)
    {
        $this->instances[$abstract] = $instance;

        return $instance;
    }

    /**
     * Resolve the given type from the container.
     *
     * @param string $abstract
     * @param array $parameters
     * @return mixed
     *
     * @throws RuntimeException
     */
    public function make(string $abstract, array $parameters = [])
    {
        // If we have an instance in the container, return it
        if (isset($this->instances[$abstract])) {
            return $this->instances[$abstract];
        }

        // Get the concrete implementation
        $concrete = $this->getConcrete($abstract);

        // If we have a binding that is not a closure, create one
        if (!$concrete instanceof Closure) {
            $concrete = $this->getClosure($abstract, $concrete);
        }

        // Build and return the instance
        $object = $this->build($concrete, $parameters);

        // If the binding is shared, store the instance
        if ($this->isShared($abstract)) {
            $this->instances[$abstract] = $object;
        }

        return $object;
    }

    /**
     * Get the concrete type for a given abstract.
     *
     * @param string $abstract
     * @return mixed
     */
    protected function getConcrete(string $abstract)
    {
        if (isset($this->bindings[$abstract])) {
            return $this->bindings[$abstract]['concrete'];
        }

        return $abstract;
    }

    /**
     * Instantiate a concrete instance of the given type.
     *
     * @param Closure $concrete
     * @param array $parameters
     * @return mixed
     *
     * @throws RuntimeException
     */
    protected function build(Closure $concrete, array $parameters = [])
    {
        return $concrete($this, $parameters);
    }

    /**
     * Determine if the given abstract type has been bound.
     *
     * @param string $abstract
     * @return bool
     */
    public function bound(string $abstract): bool
    {
        return isset($this->bindings[$abstract]) || isset($this->instances[$abstract]);
    }

    /**
     * Determine if a given type is shared.
     *
     * @param string $abstract
     * @return bool
     */
    protected function isShared(string $abstract): bool
    {
        return isset($this->instances[$abstract]) ||
            (isset($this->bindings[$abstract]) && $this->bindings[$abstract]['shared']);
    }

    /**
     * Get the Closure to be used when building a type.
     *
     * @param string $abstract
     * @param string $concrete
     * @return Closure
     */
    protected function getClosure(string $abstract, string $concrete): Closure
    {
        return function ($container, $parameters = []) use ($abstract, $concrete) {
            if ($abstract === $concrete) {
                return $this->build(function () use ($concrete, $parameters) {
                    return $this->resolve($concrete, $parameters);
                }, $parameters);
            }

            return $container->resolve($concrete, $parameters);
        };
    }

    /**
     * Resolve a class based dependency from the container.
     *
     * @param string $concrete
     * @param array $parameters
     * @return mixed
     *
     * @throws RuntimeException
     */
    protected function resolve(string $concrete, array $parameters = [])
    {
        $reflector = new ReflectionClass($concrete);

        if (!$reflector->isInstantiable()) {
            throw new RuntimeException("Target [$concrete] is not instantiable.");
        }

        $constructor = $reflector->getConstructor();

        if (is_null($constructor)) {
            return new $concrete;
        }

        $dependencies = $constructor->getParameters();
        $instances = $this->resolveDependencies($dependencies, $parameters);

        return $reflector->newInstanceArgs($instances);
    }

    /**
     * Resolve all of the dependencies from the ReflectionParameters.
     *
     * @param ReflectionParameter[] $dependencies
     * @param array $parameters
     * @return array
     *
     * @throws RuntimeException
     */
    protected function resolveDependencies(array $dependencies, array $parameters): array
    {
        $results = [];

        foreach ($dependencies as $dependency) {
            // If the parameter is in the parameters array, use that
            if (array_key_exists($dependency->getName(), $parameters)) {
                $results[] = $parameters[$dependency->getName()];
                continue;
            }

            // Get the type hint class if it exists
            $type = $dependency->getType();
            
            if (!$type || $type->isBuiltin()) {
                if ($dependency->isDefaultValueAvailable()) {
                    $results[] = $dependency->getDefaultValue();
                    continue;
                }

                throw new RuntimeException("Unresolvable dependency resolving [$dependency] in class {$dependency->getDeclaringClass()->getName()}");
            }

            // Try to resolve the class from the container
            $results[] = $this->make($type->getName());
        }

        return $results;
    }

    /**
     * Call the given callback with dependency injection.
     *
     * @param callable|array $callback
     * @param array $parameters
     * @return mixed
     */
    public function call($callback, array $parameters = [])
    {
        if ($callback instanceof Closure) {
            return $this->callClosure($callback, $parameters);
        }

        if (is_array($callback) && count($callback) === 2) {
            [$class, $method] = $callback;
            return $this->callMethod($class, $method, $parameters);
        }

        throw new RuntimeException("Invalid callback type");
    }

    /**
     * Call a closure with dependency injection.
     *
     * @param Closure $closure
     * @param array $parameters
     * @return mixed
     */
    protected function callClosure(Closure $closure, array $parameters = [])
    {
        $reflection = new \ReflectionFunction($closure);
        $dependencies = $this->resolveDependencies($reflection->getParameters(), $parameters);
        return $closure(...$dependencies);
    }

    /**
     * Call a method with dependency injection.
     *
     * @param object|string $class
     * @param string $method
     * @param array $parameters
     * @return mixed
     */
    protected function callMethod($class, string $method, array $parameters = [])
    {
        if (is_string($class)) {
            $class = $this->make($class);
        }

        $reflection = new ReflectionMethod($class, $method);
        $dependencies = $this->resolveDependencies($reflection->getParameters(), $parameters);
        return $reflection->invokeArgs($class, $dependencies);
    }
}
