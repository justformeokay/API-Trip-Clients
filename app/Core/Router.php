<?php

declare(strict_types=1);

namespace App\Core;

use App\Exceptions\NotFoundException;
use App\Exceptions\MethodNotAllowedException;

/**
 * HTTP Router with middleware pipeline support.
 * Handles versioned REST API routing.
 */
final class Router
{
    /** @var array<string, list<array{pattern: string, handler: callable|array, middleware: list<string>}>> */
    private array $routes = [];

    /** @var list<string> */
    private array $globalMiddleware = [];

    /** @var array<string, list<string>> */
    private array $groupMiddleware = [];

    private ?string $currentPrefix    = null;
    private array   $currentMiddleware = [];

    public function __construct(private readonly Container $container) {}

    // -------------------------------------------------------------------------
    // Route registration helpers
    // -------------------------------------------------------------------------

    public function get(string $path, array|callable $handler): self
    {
        return $this->addRoute('GET', $path, $handler);
    }

    public function post(string $path, array|callable $handler): self
    {
        return $this->addRoute('POST', $path, $handler);
    }

    public function put(string $path, array|callable $handler): self
    {
        return $this->addRoute('PUT', $path, $handler);
    }

    public function patch(string $path, array|callable $handler): self
    {
        return $this->addRoute('PATCH', $path, $handler);
    }

    public function delete(string $path, array|callable $handler): self
    {
        return $this->addRoute('DELETE', $path, $handler);
    }

    public function options(string $path, array|callable $handler): self
    {
        return $this->addRoute('OPTIONS', $path, $handler);
    }

    /**
     * Group routes under a prefix and/or shared middleware.
     */
    public function group(array $attributes, callable $callback): void
    {
        $previousPrefix     = $this->currentPrefix;
        $previousMiddleware = $this->currentMiddleware;

        $this->currentPrefix = $previousPrefix . ($attributes['prefix'] ?? '');
        $this->currentMiddleware = array_merge(
            $previousMiddleware,
            $attributes['middleware'] ?? []
        );

        $callback($this);

        $this->currentPrefix     = $previousPrefix;
        $this->currentMiddleware = $previousMiddleware;
    }

    /**
     * Add global middleware that runs on every request.
     */
    public function addGlobalMiddleware(string ...$middleware): void
    {
        foreach ($middleware as $m) {
            $this->globalMiddleware[] = $m;
        }
    }

    // -------------------------------------------------------------------------
    // Dispatch
    // -------------------------------------------------------------------------

    public function dispatch(Request $request): Response
    {
        $method = $request->getMethod();
        $uri    = $request->getPath();

        // Collect all routes for this method + HEAD/ANY matches
        $methodRoutes  = $this->routes[$method]  ?? [];
        $matchedMethod = false;

        foreach ($this->routes as $routeMethod => $routes) {
            foreach ($routes as $route) {
                $params = [];
                if ($this->matchUri($route['pattern'], $uri, $params)) {
                    $matchedMethod = true;
                    if ($routeMethod === $method) {
                        $request->setRouteParams($params);
                        return $this->runPipeline($request, $route);
                    }
                }
            }
        }

        if ($matchedMethod) {
            throw new MethodNotAllowedException("Method {$method} not allowed.");
        }

        throw new NotFoundException("Route not found: {$method} {$uri}");
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    private function addRoute(string $method, string $path, array|callable $handler): self
    {
        $fullPath = ($this->currentPrefix ?? '') . '/' . ltrim($path, '/');
        $fullPath = '/' . ltrim($fullPath, '/');

        $this->routes[$method][] = [
            'pattern'    => $this->buildPattern($fullPath),
            'raw'        => $fullPath,
            'handler'    => $handler,
            'middleware' => array_merge($this->globalMiddleware, $this->currentMiddleware),
        ];

        return $this;
    }

    /**
     * Convert route path to regex, e.g. /users/{id} -> ^/users/([^/]+)$
     */
    private function buildPattern(string $path): string
    {
        $pattern = preg_replace('/\{([a-zA-Z_][a-zA-Z0-9_]*)\}/', '([^/]+)', $path);
        return '#^' . $pattern . '$#';
    }

    /**
     * Match URI against a pattern and extract named params.
     *
     * @param array<string, string> $params
     */
    private function matchUri(string $pattern, string $uri, array &$params): bool
    {
        $params = [];
        if (!preg_match($pattern, $uri, $matches)) {
            return false;
        }
        array_shift($matches);
        // Re-extract param names from the raw path
        preg_match_all('/\{([a-zA-Z_][a-zA-Z0-9_]*)\}/', $pattern, $names);
        $params = array_combine($names[1] ?? [], $matches);
        return true;
    }

    /**
     * Run middleware pipeline then invoke controller.
     */
    private function runPipeline(Request $request, array $route): Response
    {
        $middleware = $route['middleware'];
        $handler    = $route['handler'];

        $pipeline = array_reduce(
            array_reverse($middleware),
            function (callable $next, string $middlewareClass) {
                return function (Request $req) use ($middlewareClass, $next): Response {
                    /** @var \App\Middlewares\MiddlewareInterface $mw */
                    $mw = $this->container->make($middlewareClass);
                    return $mw->handle($req, $next);
                };
            },
            function (Request $req) use ($handler): Response {
                return $this->callHandler($req, $handler);
            }
        );

        return $pipeline($request);
    }

    private function callHandler(Request $request, array|callable $handler): Response
    {
        if (is_callable($handler)) {
            return $handler($request);
        }

        [$controllerClass, $method] = $handler;
        $controller = $this->container->make($controllerClass);

        return $controller->$method($request);
    }
}
