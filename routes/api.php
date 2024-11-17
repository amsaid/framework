<?php

use App\Controllers\Api\UserController;

return function($router) {
    // API Routes
    $router->group(['prefix' => 'api', 'middleware' => 'api'], function($router) {
        // Users Resource
        $router->get('/users', [UserController::class, 'index']);
        $router->get('/users/{id}', [UserController::class, 'show'])
               ->where('id', '\d+');
        $router->post('/users', [UserController::class, 'store']);
        $router->put('/users/{id}', [UserController::class, 'update'])
               ->where('id', '\d+');
        $router->delete('/users/{id}', [UserController::class, 'destroy'])
               ->where('id', '\d+');
    });
};
