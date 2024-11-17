<?php

use Core\Debug\Dump;
use Core\Environment;
use Core\Http\Response;
use Core\Config\Config;

if (!function_exists('env')) {
    /**
     * Gets the value of an environment variable
     *
     * @param string $key Environment variable name
     * @param mixed $default Default value if not found
     * @return mixed
     */
    function env(string $key, $default = null)
    {
        return Environment::get($key, $default);
    }
}

if (!function_exists('env_required')) {
    /**
     * Gets the value of a required environment variable
     * Throws RuntimeException if not found
     *
     * @param string $key Environment variable name
     * @return string
     * @throws RuntimeException
     */
    function env_required(string $key): string
    {
        return Environment::required($key);
    }
}

if (!function_exists('dump')) {
    /**
     * Dump variables with syntax highlighting and type information
     * 
     * @param mixed ...$vars Variables to dump
     * @return void
     */
    function dump(...$vars): void
    {
        Dump::dump(...$vars);
    }
}

if (!function_exists('dd')) {
    /**
     * Dump variables and die
     * 
     * @param mixed ...$vars Variables to dump
     * @return void
     */
    function dd(...$vars): void
    {
        Dump::dd(...$vars);
    }
}

if (!function_exists('get_object_vars_all')) {
    /**
     * Get all object properties including private and protected
     * 
     * @param object $obj Object to get properties from
     * @return array Array of properties
     */
    function get_object_vars_all($obj): array
    {
        $reflection = new ReflectionObject($obj);
        $properties = [];

        do {
            foreach ($reflection->getProperties() as $property) {
                $property->setAccessible(true);
                $properties[$property->getName()] = $property->getValue($obj);
            }
        } while ($reflection = $reflection->getParentClass());

        return $properties;
    }
}

if (!function_exists('config')) {
    /**
     * Get a configuration value
     * 
     * @param string $key Dot notation key (e.g. 'app.debug')
     * @param mixed $default Default value if not found
     * @return mixed
     */
    function config(string $key, $default = null)
    {
        return Config::get($key, $default);
    }
}

if (!function_exists('view')) {
    /**
     * Render a view
     * 
     * @param string $view View name
     * @param array $data Data to pass to view
     * @return string
     */
    function view(string $view, array $data = []): string
    {
        $viewPath = config('view.path', __DIR__ . '/../views');
        $extension = config('view.extension', '.php');
        
        $file = str_replace('.', '/', $view) . $extension;
        $path = rtrim($viewPath, '/') . '/' . $file;

        if (!file_exists($path)) {
            throw new RuntimeException("View not found: {$view}");
        }

        extract($data, EXTR_SKIP);
        ob_start();
        include $path;
        return ob_get_clean();
    }
}

if (!function_exists('asset')) {
    /**
     * Generate asset URL
     * 
     * @param string $path Asset path
     * @return string Full URL to asset
     */
    function asset(string $path): string
    {
        $baseUrl = rtrim(config('app.url', ''), '/');
        return $baseUrl . '/assets/' . ltrim($path, '/');
    }
}

if (!function_exists('url')) {
    /**
     * Generate URL
     * 
     * @param string $path URL path
     * @param array $params Query parameters
     * @return string Full URL
     */
    function url(string $path, array $params = []): string
    {
        $baseUrl = rtrim(config('app.url', ''), '/');
        $url = $baseUrl . '/' . ltrim($path, '/');
        
        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }
        
        return $url;
    }
}

if (!function_exists('redirect')) {
    /**
     * Create a redirect response
     * 
     * @param string $url URL to redirect to
     * @param int $status HTTP status code
     * @return Response
     */
    function redirect(string $url, int $status = 302): Response
    {
        return new Response('', $status, ['Location' => $url]);
    }
}

if (!function_exists('session')) {
    /**
     * Get/set session value
     * 
     * @param string|null $key Key to get/set
     * @param mixed $default Default value if getting
     * @return mixed
     */
    function session(?string $key = null, $default = null)
    {
        if (!isset($_SESSION)) {
            session_start();
        }

        if ($key === null) {
            return $_SESSION;
        }

        if (func_num_args() === 1) {
            return $_SESSION[$key] ?? $default;
        }

        $_SESSION[$key] = $default;
        return $default;
    }
}

if (!function_exists('csrf_token')) {
    /**
     * Generate or retrieve CSRF token
     * 
     * @return string CSRF token
     */
    function csrf_token(): string
    {
        if (!isset($_SESSION)) {
            session_start();
        }

        if (!isset($_SESSION['_csrf'])) {
            $_SESSION['_csrf'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['_csrf'];
    }
}

if (!function_exists('csrf_field')) {
    /**
     * Generate CSRF field HTML
     * 
     * @return string HTML input field
     */
    function csrf_field(): string
    {
        return '<input type="hidden" name="_csrf" value="' . csrf_token() . '">';
    }
}

if (!function_exists('old')) {
    /**
     * Get old input value
     * 
     * @param string $key Input key
     * @param mixed $default Default value
     * @return mixed
     */
    function old(string $key, $default = '')
    {
        return session('_old.' . $key, $default);
    }
}

if (!function_exists('flash')) {
    /**
     * Flash message to session
     * 
     * @param string $key Message key
     * @param mixed $value Message value
     * @return void
     */
    function flash(string $key, $value): void
    {
        session('_flash.' . $key, $value);
    }
}

if (!function_exists('sanitize')) {
    /**
     * Sanitize output for HTML display
     * 
     * @param mixed $value Value to sanitize
     * @return string Sanitized value
     */
    function sanitize($value): string
    {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('is_ajax')) {
    /**
     * Check if request is AJAX
     * 
     * @return bool
     */
    function is_ajax(): bool
    {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }
}

if (!function_exists('json')) {
    /**
     * Create JSON response
     * 
     * @param mixed $data Data to encode
     * @param int $status HTTP status code
     * @return Response
     */
    function json($data, int $status = 200): Response
    {
        return new Response(
            json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
            $status,
            ['Content-Type' => 'application/json']
        );
    }
}
