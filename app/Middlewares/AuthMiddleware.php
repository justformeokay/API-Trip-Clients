<?php

declare(strict_types=1);

namespace App\Middlewares;

use App\Core\Request;
use App\Core\Response;
use App\Exceptions\AuthenticationException;
use App\Services\JwtService;

/**
 * JWT Authentication middleware.
 * Validates the Bearer access token, injects user data into the request.
 */
final class AuthMiddleware implements MiddlewareInterface
{
    public function __construct(private readonly JwtService $jwtService) {}

    public function handle(Request $request, callable $next): Response
    {
        $token = $request->bearerToken();

        if (empty($token)) {
            throw new AuthenticationException('Access token is required.');
        }

        $payload = $this->jwtService->validateAccessToken($token);

        // Inject claims into request attributes for downstream use
        $request->setAttribute('user_id',    (int)    ($payload['sub']   ?? 0));
        $request->setAttribute('user_email', (string) ($payload['email'] ?? ''));
        $request->setAttribute('user_role',  (string) ($payload['role']  ?? 'user'));

        return $next($request);
    }
}
