<?php

namespace Core;

use RuntimeException;
use Core\Config\Config;

class View
{
    private string $view;
    private array $params;
    private ?string $layout = null;
    private array $sections = [];
    private string $currentSection = '';
    private static array $globals = [];

    public function __construct(string $view, array $params = [])
    {
        $this->view = $view;
        $this->params = $params;
        $this->layout = Config::get('view.default_layout', 'default');
    }

    /**
     * Set global view data available to all views
     *
     * @param string $key Global variable key
     * @param mixed $value Global variable value
     */
    public static function share(string $key, $value): void
    {
        static::$globals[$key] = $value;
    }

    /**
     * Set or get layout
     *
     * @param string|null $layout Layout name or null to disable layout
     * @return self
     */
    public function layout(?string $layout): self
    {
        $this->layout = $layout;
        return $this;
    }

    /**
     * Get section content
     *
     * @param string $name Section name
     * @param string $default Default content if section not found
     * @return string Section content
     */
    public function section(string $name, string $default = ''): string
    {
        return $this->sections[$name] ?? $default;
    }

    /**
     * Start a new section
     *
     * @param string $name Section name
     * @throws RuntimeException If there's already an active section
     */
    public function startSection(string $name): void
    {
        if (!empty($this->currentSection)) {
            throw new RuntimeException('Cannot nest sections');
        }
        $this->currentSection = $name;
        ob_start();
    }

    /**
     * End the current section
     *
     * @throws RuntimeException If no section is active
     */
    public function endSection(): void
    {
        if (empty($this->currentSection)) {
            throw new RuntimeException('No active section to end');
        }
        $this->sections[$this->currentSection] = ob_get_clean();
        $this->currentSection = '';
    }

    /**
     * Escape HTML entities in a string
     *
     * @param mixed $value Value to escape
     * @return string Escaped string
     */
    public function e($value): string
    {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }

    /**
     * Include a partial view
     *
     * @param string $view View name
     * @param array $params Parameters to pass to the view
     * @return string Rendered partial content
     */
    public function partial(string $view, array $params = []): string
    {
        return (new static($view, $params))->layout(null)->render();
    }

    /**
     * Render the view
     *
     * @return string Rendered content
     * @throws RuntimeException If view file not found
     */
    public function render(): string
    {
        // Get view paths from config
        $viewPath = rtrim(Config::get('view.path', dirname(__DIR__) . '/app/Views'), '/');
        $layoutPath = rtrim(Config::get('view.layout_path', $viewPath . '/layouts'), '/');
        
        // Convert dot notation to path
        $viewFile = str_replace('.', '/', $this->view) . '.php';
        $fullViewPath = $viewPath . '/' . $viewFile;

        if (!file_exists($fullViewPath)) {
            throw new RuntimeException("View not found: {$this->view}");
        }

        // Extract parameters and globals
        extract($this->params);
        extract(static::$globals);

        // Start output buffering for the view content
        ob_start();
        include $fullViewPath;
        $content = ob_get_clean();

        // If no layout is specified, return the content directly
        if ($this->layout === null) {
            return $content;
        }

        // Otherwise, render the content within the layout
        $layoutFile = $layoutPath . '/' . $this->layout . '.php';
        
        if (!file_exists($layoutFile)) {
            throw new RuntimeException("Layout not found: {$this->layout}");
        }

        // Start output buffering for the layout
        ob_start();
        include $layoutFile;
        return ob_get_clean();
    }

    /**
     * Get asset URL
     *
     * @param string $path Asset path
     * @return string Full asset URL
     */
    public function asset(string $path): string
    {
        return asset($path);
    }

    /**
     * Generate URL
     *
     * @param string $path URL path
     * @param array $params Query parameters
     * @return string Full URL
     */
    public function url(string $path, array $params = []): string
    {
        return url($path, $params);
    }

    /**
     * Get CSRF token field
     *
     * @return string CSRF token field HTML
     */
    public function csrf(): string
    {
        return csrf_field();
    }

    /**
     * Get old input value
     *
     * @param string $key Input key
     * @param mixed $default Default value
     * @return mixed Old input value
     */
    public function old(string $key, $default = '')
    {
        return old($key, $default);
    }
}
