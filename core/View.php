<?php

namespace Core;

class View
{
    private string $view;
    private array $params;
    private ?string $layout = 'default';
    private array $sections = [];
    private string $currentSection = '';

    public function __construct(string $view, array $params = [])
    {
        $this->view = $view;
        $this->params = $params;
    }

    public function setLayout(?string $layout): self
    {
        $this->layout = $layout;
        return $this;
    }

    public function section(string $name): ?string
    {
        return $this->sections[$name] ?? null;
    }

    public function startSection(string $name): void
    {
        $this->currentSection = $name;
        ob_start();
    }

    public function endSection(): void
    {
        if (!empty($this->currentSection)) {
            $this->sections[$this->currentSection] = ob_get_clean();
            $this->currentSection = '';
        }
    }

    public function render(): string
    {
        $viewPath = dirname(__DIR__) . "/app/Views/{$this->view}.php";
        
        if (!file_exists($viewPath)) {
            throw new \Exception("View {$this->view} not found");
        }

        // Extract parameters to make them available in the view
        extract($this->params);

        // Start output buffering for the view content
        ob_start();
        include $viewPath;
        $content = ob_get_clean();

        // If no layout is specified, return the content directly
        if ($this->layout === null) {
            return $content;
        }

        // Otherwise, render the content within the layout
        $layoutPath = dirname(__DIR__) . "/app/Views/layouts/{$this->layout}.php";
        
        if (!file_exists($layoutPath)) {
            throw new \Exception("Layout {$this->layout} not found");
        }

        // Start output buffering for the layout
        ob_start();
        include $layoutPath;
        return ob_get_clean();
    }
}
