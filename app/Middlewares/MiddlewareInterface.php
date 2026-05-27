<?php

declare(strict_types=1);

namespace App\Middlewares;

use App\Core\Request;
use App\Core\Response;

/**
 * All middleware must implement this contract.
 */
interface MiddlewareInterface
{
    public function handle(Request $request, callable $next): Response;
}
