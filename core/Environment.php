<?php

namespace Core;

class Environment
{
    private static array $variables = [];
    private static bool $loaded = false;

    public static function load(string $path = null): void
    {
        if (self::$loaded) {
            return;
        }

        $path = $path ?? dirname(__DIR__) . '/.env';

        if (!file_exists($path)) {
            throw new \RuntimeException('.env file not found');
        }

        // Read .env file
        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        foreach ($lines as $line) {
            // Skip comments
            if (strpos(trim($line), '#') === 0) {
                continue;
            }

            // Parse line
            if (strpos($line, '=') !== false) {
                list($name, $value) = array_map('trim', explode('=', $line, 2));
                
                // Remove quotes if present
                $value = trim($value, '"\'');
                
                // Set environment variable
                self::$variables[$name] = $value;
                putenv("{$name}={$value}");
                $_ENV[$name] = $value;
                $_SERVER[$name] = $value;
            }
        }

        self::$loaded = true;
    }

    public static function get(string $key, $default = null)
    {
        return self::$variables[$key] ?? $default;
    }

    public static function has(string $key): bool
    {
        return isset(self::$variables[$key]);
    }

    public static function all(): array
    {
        return self::$variables;
    }

    public static function required(string $key): string
    {
        if (!self::has($key)) {
            throw new \RuntimeException("Required environment variable '{$key}' is not set");
        }

        return self::get($key);
    }
}
