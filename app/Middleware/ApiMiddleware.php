<?php

namespace App\Middleware;

use Core\Request;
use Core\Response\JsonResponse;

class ApiMiddleware
{
    public function handle(Request $request, \Closure $next)
    {
        // Check if request accepts JSON
        $acceptHeader = $request->header('Accept');
        if ($acceptHeader && !str_contains($acceptHeader, 'application/json')) {
            return JsonResponse::error('API requires Accept: application/json header', 406);
        }

        // Add CORS headers for API requests
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization');

        // Handle preflight requests
        if ($request->method() === 'OPTIONS') {
            return JsonResponse::success(null, 204);
        }

        return $next($request);
    }
}
