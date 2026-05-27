<?php

declare(strict_types=1);

namespace App\Middlewares;

use App\Core\Request;
use App\Core\Response;

/**
 * Request/response structured logging middleware.
 * Logs method, path, status code, response time, and IP.
 */
final class LoggingMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, callable $next): Response
    {
        $startTime = microtime(true);

        $response = $next($request);

        $duration = round((microtime(true) - $startTime) * 1000, 2);

        $this->writeLog([
            'time'     => date('Y-m-d H:i:s'),
            'method'   => $request->getMethod(),
            'path'     => $request->getPath(),
            'status'   => $response->getStatusCode(),
            'duration' => $duration . 'ms',
            'ip'       => $request->getIp(),
            'ua'       => substr($request->getUserAgent(), 0, 120),
        ]);

        return $response;
    }

    private function writeLog(array $data): void
    {
        $logPath = $_ENV['LOG_PATH'] ?? 'storage/logs/app.log';
        $logDir  = dirname($logPath);

        if (!is_dir($logDir)) {
            @mkdir($logDir, 0755, true);
        }

        $line = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n";
        @file_put_contents($logPath, $line, FILE_APPEND | LOCK_EX);
    }
}
