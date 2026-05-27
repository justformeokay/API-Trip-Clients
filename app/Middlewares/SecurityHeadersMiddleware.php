<?php

declare(strict_types=1);

namespace App\Middlewares;

use App\Core\Request;
use App\Core\Response;

/**
 * Adds OWASP-recommended security headers to every response.
 */
final class SecurityHeadersMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, callable $next): Response
    {
        $response = $next($request);

        return $response
            // Prevent MIME-type sniffing
            ->withHeader('X-Content-Type-Options', 'nosniff')
            // Clickjacking protection
            ->withHeader('X-Frame-Options', 'DENY')
            // XSS filter (legacy browsers)
            ->withHeader('X-XSS-Protection', '1; mode=block')
            // Strict Transport Security (1 year)
            ->withHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains; preload')
            // Content Security Policy — API only, no inline scripts
            ->withHeader('Content-Security-Policy', "default-src 'none'; frame-ancestors 'none'")
            // Referrer policy
            ->withHeader('Referrer-Policy', 'no-referrer')
            // Permissions policy
            ->withHeader('Permissions-Policy', 'geolocation=(), camera=(), microphone=()')
            // Cache control for API responses
            ->withHeader('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->withHeader('Pragma', 'no-cache');
    }
}
