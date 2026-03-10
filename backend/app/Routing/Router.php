<?php
namespace App\Routing;

class Router
{
    private array $routes = [];

    public function map(string|array $methods, string $pattern, callable $handler): void
    {
        foreach ((array)$methods as $method) {
            $this->routes[] = [
                'method' => strtoupper($method),
                'pattern' => $pattern,
                'handler' => $handler,
            ];
        }
    }

    public function dispatch(string $method, string $uriPath): bool
    {
        $method = strtoupper($method);
        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }

            $params = $this->match($route['pattern'], $uriPath);
            if ($params === null) {
                continue;
            }

            ($route['handler'])($params);
            return true;
        }

        return false;
    }

    private function match(string $pattern, string $path): ?array
    {
        if ($pattern === $path) {
            return [];
        }

        $regex = preg_replace_callback(
            '/\{([a-zA-Z_][a-zA-Z0-9_]*)\}/',
            static fn(array $m) => '(?P<' . $m[1] . '>[^/]+)',
            $pattern
        );

        if (!is_string($regex)) {
            return null;
        }

        $regex = '#^' . $regex . '$#';
        if (!preg_match($regex, $path, $matches)) {
            return null;
        }

        $params = [];
        foreach ($matches as $key => $value) {
            if (!is_int($key)) {
                $params[$key] = $value;
            }
        }

        return $params;
    }
}
