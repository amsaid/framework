<?php

use App\Controllers\HomeController;
use App\Controllers\AuthController;
use App\Controllers\DashboardController;
use App\Controllers\ProfileController;

return function($router) {
    // Public routes
    $router->get('/', [HomeController::class, 'index'])->name('home');
    $router->get('/about', [HomeController::class, 'about'])->name('about');

    // Auth routes
    $router->group(['prefix' => 'auth'], function($router) {
        $router->post('/login', [AuthController::class, 'login']);
        $router->post('/logout', [AuthController::class, 'logout'])->name('auth.logout');
    });

    // Protected routes
    $router->group(['middleware' => ['auth']], function($router) {
        $router->get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
        $router->get('/profile', [ProfileController::class, 'index'])->name('profile');
    });
};
