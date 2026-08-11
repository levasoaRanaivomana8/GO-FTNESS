<?php

declare(strict_types=1);

namespace Core;

final class Router
{
    private array $routes = [];

    public function get(string $path, array $handler): void
    {
        $this->routes['GET'][$path] = $handler;
    }

    public function post(string $path, array $handler): void
    {
        $this->routes['POST'][$path] = $handler;
    }

    public function dispatch(string $uri, string $method): void
    {
        $path = parse_url($uri, PHP_URL_PATH) ?: '/';

        // IMPORTANT: si ton projet est dans /go-fitness/public/
        // l’URL peut contenir /go-fitness/public au début.
        // On nettoie pour matcher les routes.
        $path = str_replace('/go-fitness/public', '', $path);
        if ($path === '') $path = '/';


        $handler = $this->routes[$method][$path] ?? null;

        if (!$handler) {
            http_response_code(404);
            echo "404 Not Found ($path)";
            return;
        }

        [$class, $action] = $handler;

        $controller = new $class();
        $controller->$action();
    }
}
