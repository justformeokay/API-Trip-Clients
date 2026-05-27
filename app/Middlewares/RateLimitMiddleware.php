<?php

declare(strict_types=1);

namespace App\Middlewares;

use App\Core\Request;
use App\Core\Response;
use App\Exceptions\RateLimitException;
use App\Infrastructure\Database\Connection;

/**
 * Token-bucket rate limiting using the database.
 * Tracks requests per IP per time window.
 * Configurable limits for general and login-specific routes.
 */
final class RateLimitMiddleware implements MiddlewareInterface
{
    private bool $isLoginEndpoint;

    public function __construct(
        private readonly Connection $db,
        bool $isLoginEndpoint = false
    ) {
        $this->isLoginEndpoint = $isLoginEndpoint;
    }

    public function handle(Request $request, callable $next): Response
    {
        if (!filter_var($_ENV['RATE_LIMIT_ENABLED'] ?? true, FILTER_VALIDATE_BOOLEAN)) {
            return $next($request);
        }

        $ip     = $request->getIp();
        $route  = $request->getPath();
        $key    = md5($ip . ':' . ($this->isLoginEndpoint ? 'login' : 'general'));

        $limit  = $this->isLoginEndpoint
            ? (int) ($_ENV['RATE_LIMIT_LOGIN_REQUESTS'] ?? 5)
            : (int) ($_ENV['RATE_LIMIT_REQUESTS']       ?? 60);

        $window = $this->isLoginEndpoint
            ? (int) ($_ENV['RATE_LIMIT_LOGIN_WINDOW'] ?? 300)
            : (int) ($_ENV['RATE_LIMIT_WINDOW']       ?? 60);

        $this->cleanExpired($key);

        $count = $this->getRequestCount($key, $window);

        if ($count >= $limit) {
            $retryAfter = $this->getRetryAfter($key, $window);
            throw new RateLimitException(
                "Rate limit exceeded. Try again in {$retryAfter} seconds."
            );
        }

        $this->recordRequest($key, $ip, $route);

        $response = $next($request);

        return $response
            ->withHeader('X-RateLimit-Limit',     (string) $limit)
            ->withHeader('X-RateLimit-Remaining', (string) max(0, $limit - $count - 1))
            ->withHeader('X-RateLimit-Window',    (string) $window);
    }

    private function getRequestCount(string $key, int $window): int
    {
        $row = $this->db->fetchOne(
            'SELECT COUNT(*) AS cnt FROM rate_limits
             WHERE bucket_key = :key AND created_at > DATE_SUB(NOW(), INTERVAL :window SECOND)',
            [':key' => $key, ':window' => $window]
        );
        return (int) ($row['cnt'] ?? 0);
    }

    private function recordRequest(string $key, string $ip, string $route): void
    {
        $this->db->execute(
            'INSERT INTO rate_limits (bucket_key, ip_address, route, created_at) VALUES (:key, :ip, :route, NOW())',
            [':key' => $key, ':ip' => $ip, ':route' => substr($route, 0, 255)]
        );
    }

    private function cleanExpired(string $key): void
    {
        // Probabilistic cleanup (1% of requests) to avoid overhead
        if (random_int(1, 100) === 1) {
            $this->db->execute(
                'DELETE FROM rate_limits WHERE created_at < DATE_SUB(NOW(), INTERVAL 1 HOUR)'
            );
        }
    }

    private function getRetryAfter(string $key, int $window): int
    {
        $row = $this->db->fetchOne(
            'SELECT MIN(created_at) AS oldest FROM rate_limits WHERE bucket_key = :key',
            [':key' => $key]
        );
        if (!$row || !$row['oldest']) {
            return $window;
        }
        $elapsed = time() - strtotime($row['oldest']);
        return max(1, $window - $elapsed);
    }
}
