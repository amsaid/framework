<?php

namespace App\Middleware;

use Core\Middleware\MiddlewareInterface;
use Core\Exceptions\UnauthorizedException;

class AuthMiddleware implements MiddlewareInterface
{
    public function handle(object $request, \Closure $next)
    {
        // Example authentication check
        $isAuthenticated = isset($_SESSION['user_id']);
        
        if (!$isAuthenticated) {
            throw new UnauthorizedException('Please log in to access this resource');
        }
        
        return $next($request);
    }
}
