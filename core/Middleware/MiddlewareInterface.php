<?php

namespace Core\Middleware;

interface MiddlewareInterface
{
    public function handle(object $request, \Closure $next);
}
