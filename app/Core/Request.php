<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Immutable HTTP Request wrapper.
 * Provides secure access to superglobals with input sanitization.
 */
final class Request
{
    private array $routeParams  = [];
    private array $attributes   = [];

    private function __construct(
        private readonly string $method,
        private readonly string $uri,
        private readonly array  $queryParams,
        private readonly array  $bodyParams,
        private readonly array  $headers,
        private readonly array  $serverParams,
        private readonly array  $files,
        private readonly ?string $rawBody
    ) {}

    public static function capture(): self
    {
        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

        // Support method override via _method field
        if ($method === 'POST' && isset($_POST['_method'])) {
            $override = strtoupper($_POST['_method']);
            if (in_array($override, ['PUT', 'PATCH', 'DELETE'], true)) {
                $method = $override;
            }
        }

        $rawBody = file_get_contents('php://input') ?: null;
        $body    = [];

        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        if (str_contains($contentType, 'application/json') && $rawBody !== null) {
            $decoded = json_decode($rawBody, true);
            $body    = is_array($decoded) ? $decoded : [];
        } else {
            $body = $_POST;
        }

        return new self(
            method:       $method,
            uri:          self::parseUri(),
            queryParams:  $_GET,
            bodyParams:   $body,
            headers:      self::parseHeaders(),
            serverParams: $_SERVER,
            files:        $_FILES,
            rawBody:      $rawBody
        );
    }

    // -------------------------------------------------------------------------
    // Getters
    // -------------------------------------------------------------------------

    public function getMethod(): string
    {
        return $this->method;
    }

    public function getPath(): string
    {
        return parse_url($this->uri, PHP_URL_PATH) ?? '/';
    }

    public function getUri(): string
    {
        return $this->uri;
    }

    /**
     * Get a query parameter (GET), optionally with a default.
     */
    public function query(string $key, mixed $default = null): mixed
    {
        return $this->queryParams[$key] ?? $default;
    }

    /**
     * Get all query parameters.
     */
    public function allQuery(): array
    {
        return $this->queryParams;
    }

    /**
     * Get a body parameter (POST/JSON), optionally with a default.
     */
    public function input(string $key, mixed $default = null): mixed
    {
        return $this->bodyParams[$key] ?? $default;
    }

    /**
     * Get all body parameters.
     */
    public function all(): array
    {
        return array_merge($this->queryParams, $this->bodyParams);
    }

    /**
     * Get a route parameter (e.g. {id} from /trips/{id}).
     */
    public function param(string $key, mixed $default = null): mixed
    {
        return $this->routeParams[$key] ?? $default;
    }

    public function setRouteParams(array $params): void
    {
        $this->routeParams = $params;
    }

    public function getRouteParams(): array
    {
        return $this->routeParams;
    }

    /**
     * Set a request attribute (e.g. authenticated user data set by middleware).
     */
    public function setAttribute(string $key, mixed $value): void
    {
        $this->attributes[$key] = $value;
    }

    /**
     * Get a request attribute set by middleware.
     */
    public function getAttribute(string $key, mixed $default = null): mixed
    {
        return $this->attributes[$key] ?? $default;
    }

    /**
     * Get an HTTP header (case-insensitive).
     */
    public function header(string $name, ?string $default = null): ?string
    {
        $normalized = strtolower(str_replace('_', '-', $name));
        return $this->headers[$normalized] ?? $default;
    }

    /**
     * Get the Bearer token from Authorization header.
     */
    public function bearerToken(): ?string
    {
        $auth = $this->header('authorization');
        if ($auth && str_starts_with($auth, 'Bearer ')) {
            return substr($auth, 7);
        }
        return null;
    }

    public function getIp(): string
    {
        $candidates = [
            'HTTP_CF_CONNECTING_IP',
            'HTTP_X_REAL_IP',
            'HTTP_X_FORWARDED_FOR',
            'REMOTE_ADDR',
        ];

        foreach ($candidates as $key) {
            if (!empty($this->serverParams[$key])) {
                $ip = trim(explode(',', $this->serverParams[$key])[0]);
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }

        return '0.0.0.0';
    }

    public function getUserAgent(): string
    {
        return $this->serverParams['HTTP_USER_AGENT'] ?? 'unknown';
    }

    public function isJson(): bool
    {
        return str_contains($this->serverParams['CONTENT_TYPE'] ?? '', 'application/json');
    }

    public function getRawBody(): ?string
    {
        return $this->rawBody;
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    private static function parseUri(): string
    {
        $requestUri  = $_SERVER['REQUEST_URI']  ?? '/';
        $scriptName  = $_SERVER['SCRIPT_NAME']  ?? '';

        // Strip script name/directory prefix for sub-directory installations
        $basePath = dirname($scriptName);
        if ($basePath !== '/' && str_starts_with($requestUri, $basePath)) {
            $requestUri = substr($requestUri, strlen($basePath));
        }

        return $requestUri ?: '/';
    }

    private static function parseHeaders(): array
    {
        $headers = [];

        if (function_exists('getallheaders')) {
            foreach (getallheaders() as $name => $value) {
                $headers[strtolower($name)] = $value;
            }
        } else {
            foreach ($_SERVER as $key => $value) {
                if (str_starts_with($key, 'HTTP_')) {
                    $name = str_replace('_', '-', strtolower(substr($key, 5)));
                    $headers[$name] = $value;
                }
            }
        }

        return $headers;
    }
}
