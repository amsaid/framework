<?php

/**
 * Middleware Configuration
 * 
 * Define your middleware aliases, groups, and global middleware here
 */

return [
    // Middleware aliases - map short names to full class names
    'aliases' => [
        'auth' => App\Middleware\AuthMiddleware::class,
        'cors' => App\Middleware\CorsMiddleware::class,
        'admin' => App\Middleware\AdminMiddleware::class,
        'api' => App\Middleware\ApiMiddleware::class,
        'throttle' => App\Middleware\ThrottleMiddleware::class,
        'csrf' => App\Middleware\CsrfMiddleware::class,
    ],

    // Middleware groups - predefined sets of middleware
    'groups' => [
        'web' => [
            'csrf',
        ],
        'api' => [
            'cors',
            'throttle',
            'api',
        ],
        'admin' => [
            'auth',
            'admin',
        ],
    ],

    // Global middleware - applied to all routes
    'global' => [
    ],

    // Priority order - middleware execution order (first to last)
    'priority' => [
        'cors',
        'throttle',
        'csrf',
        'auth',
        'admin',
        'api',
    ]
];
