<?php

namespace Core;

use Core\Http\Response;

abstract class Controller
{
    /**
     * Render a view with parameters
     *
     * @param string $view View name (dot notation)
     * @param array $params View parameters
     * @return string Rendered view content
     * @throws \RuntimeException If view not found
     */
    protected function render(string $view, array $params = []): string
    {
        return (new View($view, $params))->render();
    }

    /**
     * Return JSON response
     *
     * @param mixed $data Data to encode as JSON
     * @param int $status HTTP status code
     * @param array $headers Additional headers
     * @return Response
     */
    protected function json($data, int $status = 200, array $headers = []): Response
    {
        return json($data, $status);
    }

    /**
     * Redirect to another URL
     *
     * @param string $url URL to redirect to
     * @param int $status HTTP status code
     * @return Response
     */
    protected function redirect(string $url, int $status = 302): Response
    {
        return redirect($url, $status);
    }

    /**
     * Get current request
     *
     * @return Request
     */
    protected function request(): Request
    {
        return Application::getInstance()->getRequest();
    }

    /**
     * Get configuration value
     *
     * @param string $key Configuration key in dot notation
     * @param mixed $default Default value if not found
     * @return mixed Configuration value
     */
    protected function config(string $key, $default = null)
    {
        return config($key, $default);
    }
}
