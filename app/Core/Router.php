<?php

declare(strict_types=1);

namespace App\Core;

use App\Services\RateLimiter;

final class Router
{
    private array $routes = [];

    public function get(string $path, array|callable $handler, array $middleware = []): void
    {
        $this->add('GET', $path, $handler, $middleware);
    }

    public function post(string $path, array|callable $handler, array $middleware = []): void
    {
        $this->add('POST', $path, $handler, $middleware);
    }

    public function add(string $method, string $path, array|callable $handler, array $middleware = []): void
    {
        $this->routes[] = compact('method', 'path', 'handler', 'middleware');
    }

    public function dispatch(Request $request): mixed
    {
        foreach ($this->routes as $route) {
            $params = $this->match($route['path'], $request->path());
            if ($route['method'] !== $request->method() || $params === null) {
                continue;
            }

            $this->runMiddleware($route['middleware'], $request);
            return $this->call($route['handler'], $request, $params);
        }

        http_response_code(404);
        return View::render('errors/404', ['title' => 'Page not found']);
    }

    private function match(string $routePath, string $requestPath): ?array
    {
        $routePath = '/' . trim($routePath, '/');
        $pattern = preg_replace('#\{([a-zA-Z_][a-zA-Z0-9_]*)\}#', '(?P<$1>[^/]+)', $routePath);
        $pattern = '#^' . $pattern . '$#';

        if (!preg_match($pattern, $requestPath, $matches)) {
            return null;
        }

        return array_filter($matches, static fn ($key) => is_string($key), ARRAY_FILTER_USE_KEY);
    }

    private function runMiddleware(array $middleware, Request $request): void
    {
        foreach ($middleware as $name) {
            match ($name) {
                'auth' => Auth::requireLogin(),
                'guest' => Auth::redirectIfAuthenticated(),
                'csrf' => $this->verifyCsrf($request),
                default => str_starts_with($name, 'throttle:')
                    ? $this->throttle($request, $name)
                    : $this->authorize($name),
            };
        }
    }

    private function throttle(Request $request, string $middleware): void
    {
        [, $limits] = explode(':', $middleware, 2);
        [$attempts, $minutes] = array_pad(array_map('intval', explode(',', $limits)), 2, 1);
        $attempts = max(1, $attempts);
        $minutes = max(1, $minutes);

        $identity = Auth::id() ? 'user:' . Auth::id() : 'ip:' . $request->ip();
        $key = 'route:' . hash('sha256', $request->method() . '|' . $request->path() . '|' . $identity);
        $limiter = new RateLimiter();

        if ($limiter->tooManyAttempts($key, $attempts)) {
            http_response_code(429);
            exit('Too many requests. Please wait and try again.');
        }

        $limiter->hit($key, $minutes);
    }

    private function verifyCsrf(Request $request): void
    {
        if (!Csrf::verify((string) $request->input('_csrf', ''))) {
            http_response_code(419);
            exit('Security token expired. Refresh the page and try again.');
        }
    }

    private function authorize(string $middleware): void
    {
        if (str_starts_with($middleware, 'can:')) {
            Auth::requirePermission(substr($middleware, 4));
        }
    }

    private function call(array|callable $handler, Request $request, array $params): mixed
    {
        if (is_callable($handler)) {
            return $handler($request, $params);
        }

        [$class, $method] = $handler;
        $controller = new $class();
        return $controller->{$method}($request, ...array_values($params));
    }
}
