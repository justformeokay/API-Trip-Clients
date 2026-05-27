<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Core\Response;
use App\Helpers\ResponseHelper;
use Throwable;

/**
 * Global exception handler — converts all Throwables to JSON responses.
 * Hides implementation details in production.
 */
final class Handler
{
    public function render(Throwable $e): Response
    {
        $isDebug = filter_var($_ENV['APP_DEBUG'] ?? false, FILTER_VALIDATE_BOOLEAN);

        $this->logException($e);

        if ($e instanceof ValidationException) {
            return ResponseHelper::error($e->getMessage(), 422, $e->getErrors());
        }

        if ($e instanceof AuthenticationException) {
            return ResponseHelper::error($e->getMessage(), 401);
        }

        if ($e instanceof AuthorizationException) {
            return ResponseHelper::error($e->getMessage(), 403);
        }

        if ($e instanceof NotFoundException) {
            return ResponseHelper::error($e->getMessage(), 404);
        }

        if ($e instanceof MethodNotAllowedException) {
            return ResponseHelper::error($e->getMessage(), 405);
        }

        if ($e instanceof RateLimitException) {
            return Response::json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 429)
            ->withHeader('Retry-After', '60');
        }

        if ($e instanceof DatabaseException) {
            $message = $isDebug ? $e->getMessage() : 'A database error occurred. Please try again later.';
            return ResponseHelper::error($message, 500);
        }

        if ($e instanceof BaseException) {
            return ResponseHelper::error($e->getMessage(), $e->getStatusCode());
        }

        // Generic / unexpected exception
        $message = $isDebug
            ? $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine()
            : 'An unexpected error occurred. Please try again later.';

        return ResponseHelper::error($message, 500);
    }

    private function logException(Throwable $e): void
    {
        $logLevel = ($e instanceof BaseException && $e->getStatusCode() < 500) ? 'warning' : 'error';
        $logPath  = $_ENV['LOG_PATH'] ?? 'storage/logs/app.log';

        $logDir = dirname($logPath);
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0755, true);
        }

        $entry = sprintf(
            "[%s] [%s] %s: %s in %s:%d\n",
            date('Y-m-d H:i:s'),
            strtoupper($logLevel),
            get_class($e),
            $e->getMessage(),
            $e->getFile(),
            $e->getLine()
        );

        @error_log($entry, 3, $logPath);
    }
}
