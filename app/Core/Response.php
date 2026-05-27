<?php

declare(strict_types=1);

namespace App\Core;

/**
 * HTTP Response builder with JSON support and security headers.
 */
final class Response
{
    private int    $statusCode = 200;
    private array  $headers    = [];
    private mixed  $body       = null;

    public function __construct()
    {
        $this->headers['Content-Type'] = 'application/json; charset=utf-8';
    }

    public static function json(mixed $data, int $status = 200): self
    {
        $response = new self();
        $response->statusCode = $status;
        $response->body       = $data;
        return $response;
    }

    public function withHeader(string $name, string $value): self
    {
        $clone = clone $this;
        $clone->headers[$name] = $value;
        return $clone;
    }

    public function withStatus(int $code): self
    {
        $clone = clone $this;
        $clone->statusCode = $code;
        return $clone;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function send(): void
    {
        if (headers_sent()) {
            return;
        }

        http_response_code($this->statusCode);

        foreach ($this->headers as $name => $value) {
            header("{$name}: {$value}");
        }

        if ($this->body !== null) {
            echo json_encode($this->body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
    }
}
