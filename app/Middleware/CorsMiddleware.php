<?php

namespace App\Middleware;

use Core\Middleware\MiddlewareInterface;

class CorsMiddleware implements MiddlewareInterface
{
    public function handle(object $request, \Closure $next)
    {
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization');
        
        if ($request->getMethod() === 'OPTIONS') {
            header('HTTP/1.1 200 OK');
            exit;
        }
        
        return $next($request);
    }
}
