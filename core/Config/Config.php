<?php

namespace Core\Config;

use RuntimeException;
use InvalidArgumentException;

/**
 * Configuration Manager Class
 * 
 * Handles loading and accessing configuration values using dot notation
 * with memory-efficient storage and robust error handling
 */
class Config
{
    /**
     * Holds all configuration values
     *
     * @var array<string,mixed>
     */
    private static array $items = [];

    /**
     * Flag to track if configs have been loaded
     *
     * @var bool
     */
    private static bool $loaded = false;

    /**
     * Cache of resolved dot notation paths
     *
     * @var array<string,mixed>
     */
    private static array $resolvedPaths = [];

    /**
     * Maximum allowed cache size for resolved paths
     *
     * @var int
     */
    private static int $maxCacheSize = 100;

    /**
     * Load all configuration files
     *
     * @param string|null $path Custom config path
     * @return void
     * @throws RuntimeException If config directory not found or invalid config file
     */
    public static function load(?string $path = null): void
    {
        if (static::$loaded) {
            return;
        }

        $configPath = $path ?? dirname(__DIR__, 2) . '/config';

        if (!is_dir($configPath)) {
            throw new RuntimeException(
                sprintf('Configuration directory not found: %s', $configPath)
            );
        }

        $files = glob($configPath . '/*.php');
        if ($files === false) {
            throw new RuntimeException(
                sprintf('Failed to read configuration directory: %s', $configPath)
            );
        }

        foreach ($files as $file) {
            $name = basename($file, '.php');
            
            try {
                $config = require $file;
            } catch (\Throwable $e) {
                throw new RuntimeException(
                    sprintf('Error loading configuration file %s: %s', $file, $e->getMessage()),
                    0,
                    $e
                );
            }
            
            if (!is_array($config)) {
                throw new RuntimeException(
                    sprintf('Configuration file must return an array: %s', $file)
                );
            }
            
            static::$items[$name] = $config;
        }

        static::$loaded = true;
    }

    /**
     * Get a configuration value using dot notation
     *
     * @param string $key Dot notation key (e.g. 'app.debug')
     * @param mixed $default Default value if not found
     * @return mixed
     * @throws InvalidArgumentException If key is empty
     */
    public static function get(string $key, $default = null)
    {
        if ($key === '') {
            throw new InvalidArgumentException('Configuration key cannot be empty');
        }

        if (!static::$loaded) {
            static::load();
        }

        // Check cache first
        if (isset(static::$resolvedPaths[$key])) {
            return static::$resolvedPaths[$key];
        }

        $parts = explode('.', $key);
        $config = static::$items;

        foreach ($parts as $part) {
            if (!is_array($config) || !array_key_exists($part, $config)) {
                return $default;
            }
            $config = $config[$part];
        }

        // Cache the result if cache isn't full
        if (count(static::$resolvedPaths) < static::$maxCacheSize) {
            static::$resolvedPaths[$key] = $config;
        }

        return $config;
    }

    /**
     * Set a configuration value using dot notation
     *
     * @param string $key Dot notation key
     * @param mixed $value Value to set
     * @return void
     * @throws InvalidArgumentException If key is empty
     */
    public static function set(string $key, $value): void
    {
        if ($key === '') {
            throw new InvalidArgumentException('Configuration key cannot be empty');
        }

        if (!static::$loaded) {
            static::load();
        }

        // Clear cached path for this key and any parent paths
        foreach (array_keys(static::$resolvedPaths) as $cachedKey) {
            if (str_starts_with($cachedKey, $key) || str_starts_with($key, $cachedKey)) {
                unset(static::$resolvedPaths[$cachedKey]);
            }
        }

        $parts = explode('.', $key);
        $config = &static::$items;

        foreach ($parts as $i => $part) {
            if ($i === count($parts) - 1) {
                $config[$part] = $value;
                break;
            }

            if (!isset($config[$part]) || !is_array($config[$part])) {
                $config[$part] = [];
            }

            $config = &$config[$part];
        }
    }

    /**
     * Check if a configuration value exists
     *
     * @param string $key Dot notation key
     * @return bool
     * @throws InvalidArgumentException If key is empty
     */
    public static function has(string $key): bool
    {
        if ($key === '') {
            throw new InvalidArgumentException('Configuration key cannot be empty');
        }

        if (!static::$loaded) {
            static::load();
        }

        $parts = explode('.', $key);
        $config = static::$items;

        foreach ($parts as $part) {
            if (!is_array($config) || !array_key_exists($part, $config)) {
                return false;
            }
            $config = $config[$part];
        }

        return true;
    }

    /**
     * Get all configuration values
     *
     * @return array<string,mixed>
     */
    public static function all(): array
    {
        if (!static::$loaded) {
            static::load();
        }

        return static::$items;
    }

    /**
     * Reset configuration and clear caches
     *
     * @return void
     */
    public static function reset(): void
    {
        static::$items = [];
        static::$resolvedPaths = [];
        static::$loaded = false;
    }

    /**
     * Set the maximum cache size for resolved paths
     *
     * @param int $size New maximum cache size
     * @return void
     * @throws InvalidArgumentException If size is negative
     */
    public static function setMaxCacheSize(int $size): void
    {
        if ($size < 0) {
            throw new InvalidArgumentException('Cache size cannot be negative');
        }

        static::$maxCacheSize = $size;
        
        // If new size is smaller, trim cache to new size
        if (count(static::$resolvedPaths) > $size) {
            static::$resolvedPaths = array_slice(static::$resolvedPaths, 0, $size, true);
        }
    }

    /**
     * Remove a configuration value
     *
     * @param string $key Dot notation key
     * @return void
     * @throws InvalidArgumentException If key is empty
     */
    public static function remove(string $key): void
    {
        if ($key === '') {
            throw new InvalidArgumentException('Configuration key cannot be empty');
        }

        if (!static::$loaded) {
            static::load();
        }

        // Clear cached path for this key and any child paths
        foreach (array_keys(static::$resolvedPaths) as $cachedKey) {
            if (str_starts_with($cachedKey, $key)) {
                unset(static::$resolvedPaths[$cachedKey]);
            }
        }

        $parts = explode('.', $key);
        $config = &static::$items;

        $lastPart = array_pop($parts);
        
        foreach ($parts as $part) {
            if (!is_array($config) || !array_key_exists($part, $config)) {
                return;
            }
            $config = &$config[$part];
        }

        unset($config[$lastPart]);
    }

    /**
     * Merge configuration arrays
     *
     * @param string $key Base key to merge into
     * @param array<string,mixed> $array Array to merge
     * @param bool $recursive Whether to merge recursively
     * @return void
     * @throws InvalidArgumentException If key is empty
     */
    public static function merge(string $key, array $array, bool $recursive = true): void
    {
        if ($key === '') {
            throw new InvalidArgumentException('Configuration key cannot be empty');
        }

        $current = static::get($key, []);
        if (!is_array($current)) {
            $current = [];
        }

        $merged = $recursive ? array_merge_recursive($current, $array) : array_merge($current, $array);
        static::set($key, $merged);
    }
}
