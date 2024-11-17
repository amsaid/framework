<?php

use Core\Debug\Dump;
use Core\Environment;

if (!function_exists('env')) {
    /**
     * Gets the value of an environment variable
     *
     * @param string $key Environment variable name
     * @param mixed $default Default value if not found
     * @return mixed
     */
    function env(string $key, $default = null)
    {
        return Environment::get($key, $default);
    }
}

if (!function_exists('env_required')) {
    /**
     * Gets the value of a required environment variable
     * Throws RuntimeException if not found
     *
     * @param string $key Environment variable name
     * @return string
     * @throws RuntimeException
     */
    function env_required(string $key): string
    {
        return Environment::required($key);
    }
}

if (!function_exists('dump')) {
    /**
     * Dump variables with syntax highlighting and type information
     * 
     * @param mixed ...$vars Variables to dump
     * @return void
     */
    function dump(...$vars): void
    {
        Dump::dump(...$vars);
    }
}

if (!function_exists('dd')) {
    /**
     * Dump variables and die
     * 
     * @param mixed ...$vars Variables to dump
     * @return void
     */
    function dd(...$vars): void
    {
        Dump::dd(...$vars);
    }
}

if (!function_exists('get_object_vars_all')) {
    /**
     * Get all object properties including private and protected
     * 
     * @param object $obj Object to get properties from
     * @return array Array of properties
     */
    function get_object_vars_all($obj): array
    {
        $reflection = new ReflectionObject($obj);
        $properties = [];

        do {
            foreach ($reflection->getProperties() as $property) {
                $property->setAccessible(true);
                $properties[$property->getName()] = $property->getValue($obj);
            }
        } while ($reflection = $reflection->getParentClass());

        return $properties;
    }
}
