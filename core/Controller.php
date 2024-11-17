<?php

namespace Core;

abstract class Controller
{
    protected function render(string $view, array $params = []): string
    {
        return (new View($view, $params))->render();
    }

    protected function json($data): string
    {
        Application::getInstance()->getResponse()->setHeader('Content-Type', 'application/json');
        return json_encode($data);
    }

    protected function redirect(string $url): void
    {
        header('Location: ' . $url);
        exit;
    }
}
