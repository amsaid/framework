<?php

namespace Core\Debug;

class Debug
{
    private static array $queries = [];
    private static array $timeline = [];
    private static array $memory = [];
    private static float $startTime;
    private static array $customData = [];

    public static function init(): void
    {
        self::$startTime = microtime(true);
        self::addTimelinePoint('Application Start');
        self::addMemoryPoint('Initial Memory Usage');
    }

    public static function addQuery(string $query, float $time, ?array $params = null): void
    {
        self::$queries[] = [
            'query' => $query,
            'params' => $params,
            'time' => $time,
            'trace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS)
        ];
    }

    public static function addTimelinePoint(string $label): void
    {
        self::$timeline[] = [
            'label' => $label,
            'time' => microtime(true),
            'duration' => microtime(true) - self::$startTime
        ];
    }

    public static function addMemoryPoint(string $label): void
    {
        self::$memory[] = [
            'label' => $label,
            'memory' => memory_get_usage(),
            'peak' => memory_get_peak_usage()
        ];
    }

    public static function addCustomData(string $key, $value): void
    {
        self::$customData[$key] = $value;
    }

    public static function getDebugData(): array
    {
        return [
            'queries' => self::$queries,
            'timeline' => self::$timeline,
            'memory' => self::$memory,
            'custom' => self::$customData,
            'request' => [
                'method' => $_SERVER['REQUEST_METHOD'] ?? 'Unknown',
                'uri' => $_SERVER['REQUEST_URI'] ?? 'Unknown',
                'time' => date('Y-m-d H:i:s'),
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'Unknown',
                'post' => $_POST,
                'get' => $_GET,
                'session' => $_SESSION ?? [],
                'cookies' => $_COOKIE,
                'headers' => getallheaders()
            ],
            'server' => [
                'php_version' => PHP_VERSION,
                'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
                'os' => PHP_OS,
                'extensions' => get_loaded_extensions()
            ],
            'performance' => [
                'total_time' => microtime(true) - self::$startTime,
                'memory_usage' => memory_get_usage(),
                'memory_peak' => memory_get_peak_usage()
            ]
        ];
    }

    public static function renderDebugBar(): string
    {
        $data = self::getDebugData();
        ob_start();
        include dirname(__DIR__, 2) . '/app/Views/debug/debug-bar.php';
        return ob_get_clean();
    }
}
