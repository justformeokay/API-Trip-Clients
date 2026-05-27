<?php

declare(strict_types=1);

namespace App\Middlewares;

use App\Core\Request;
use App\Core\Response;
use App\Exceptions\AuthorizationException;

/**
 * Role-based authorization middleware.
 * Usage: new RoleMiddleware('admin') or new RoleMiddleware('organizer', 'admin')
 */
final class RoleMiddleware implements MiddlewareInterface
{
    /** @var string[] */
    private array $allowedRoles;

    public function __construct(string ...$roles)
    {
        $this->allowedRoles = $roles;
    }

    public function handle(Request $request, callable $next): Response
    {
        $role = (string) $request->getAttribute('user_role', '');

        if (!in_array($role, $this->allowedRoles, true)) {
            throw new AuthorizationException('You do not have permission to access this resource.');
        }

        return $next($request);
    }
}
