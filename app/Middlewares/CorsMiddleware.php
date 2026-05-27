<?php

declare(strict_types=1);

namespace App\Middlewares;

use App\Core\Request;
use App\Core\Response;

/**
 * Cross-Origin Resource Sharing (CORS) middleware.
 * Reads configuration from config/cors.php.
 */
final class CorsMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, callable $next): Response
    {
        $allowedOrigins = array_map('trim', explode(',', $_ENV['CORS_ALLOWED_ORIGINS'] ?? '*'));
        $origin         = $request->header('origin', '');

        $allowOrigin = '*';
        if ($origin && $allowedOrigins !== ['*']) {
            $allowOrigin = in_array($origin, $allowedOrigins, true) ? $origin : '';
        }

        // Handle preflight OPTIONS request
        if ($request->getMethod() === 'OPTIONS') {
            $response = Response::json(null, 204);
            return $this->addCorsHeaders($response, $allowOrigin);
        }

        $response = $next($request);
        return $this->addCorsHeaders($response, $allowOrigin);
    }

    private function addCorsHeaders(Response $response, string $origin): Response
    {
        if (empty($origin)) {
            return $response;
        }

        $methods = $_ENV['CORS_ALLOWED_METHODS'] ?? 'GET,POST,PUT,PATCH,DELETE,OPTIONS';
        $headers = $_ENV['CORS_ALLOWED_HEADERS'] ?? 'Content-Type,Authorization,X-Requested-With,X-API-Version';
        $maxAge  = $_ENV['CORS_MAX_AGE']         ?? '86400';

        return $response
            ->withHeader('Access-Control-Allow-Origin',  $origin)
            ->withHeader('Access-Control-Allow-Methods', $methods)
            ->withHeader('Access-Control-Allow-Headers', $headers)
            ->withHeader('Access-Control-Max-Age',       $maxAge)
            ->withHeader('Access-Control-Allow-Credentials', 'true')
            ->withHeader('Vary', 'Origin');
    }
}
