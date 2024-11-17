<?php

return [
    // Application settings
    'name' => env('APP_NAME', 'My Framework'),
    'env' => env('APP_ENV', 'development'),
    'debug' => env('APP_DEBUG', true),
    'url' => env('APP_URL', 'http://localhost'),

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
