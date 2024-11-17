<?php

return [
    // Application settings
    'name' => env('APP_NAME', 'My Framework'),
    'env' => env('APP_ENV', 'development'),
    'debug' => env('APP_DEBUG', true),
    'url' => env('APP_URL', 'http://localhost'),
    
    // Debug Settings
    'debug_settings' => [
        'display_errors' => env('DISPLAY_ERRORS', true),
        'error_reporting' => env('ERROR_REPORTING', E_ALL),
        'log_errors' => env('LOG_ERRORS', true),
        'error_log' => env('ERROR_LOG', dirname(__DIR__) . '/storage/logs/error.log'),
    ],
    
    // Error handling
    'error_reporting' => env('ERROR_REPORTING', E_ALL),
    'display_errors' => env('DISPLAY_ERRORS', true),
    
    // Session configuration
    'session' => [
        'name' => env('SESSION_NAME', 'my_session'),
        'lifetime' => env('SESSION_LIFETIME', 7200),
        'secure' => env('SESSION_SECURE', false),
        'httponly' => env('SESSION_HTTPONLY', true)
    ],
    
    // Default timezone
    'timezone' => env('TIMEZONE', 'UTC'),
];
