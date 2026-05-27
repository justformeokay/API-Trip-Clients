<?php

declare(strict_types=1);

/**
 * API Route Definitions — HealingYuk v1
 *
 * All routes are prefixed with /api/v1
 * Middleware stack:
 *   - CorsMiddleware         → every request
 *   - SecurityHeadersMiddleware → every request
 *   - LoggingMiddleware      → every request
 *   - RateLimitMiddleware    → configurable per group
 *   - AuthMiddleware         → protected routes only
 */

use App\Core\Router;
use App\Middlewares\AuthMiddleware;
use App\Middlewares\CorsMiddleware;
use App\Middlewares\SecurityHeadersMiddleware;
use App\Middlewares\LoggingMiddleware;
use App\Middlewares\RateLimitMiddleware;
use App\Presentation\Controllers\AuthController;
use App\Presentation\Controllers\TripController;
use App\Presentation\Controllers\SearchController;
use App\Presentation\Controllers\BookingController;
use App\Presentation\Controllers\UserController;

/** @var Router $router */

// Global middleware (runs on every request)
$router->addGlobalMiddleware(
    CorsMiddleware::class,
    SecurityHeadersMiddleware::class,
    LoggingMiddleware::class
);

// ============================================================
// API v1 — PUBLIC ROUTES
// ============================================================
$router->group(['prefix' => '/api/v1'], function (Router $router) {

    // Health check
    $router->get('/health', fn() => \App\Core\Response::json([
        'success' => true,
        'message' => 'HealingYuk API is running.',
        'version' => $_ENV['APP_VERSION'] ?? '1.0.0',
        'time'    => date('c'),
    ]));

    // --------------------------------------------------------
    // AUTHENTICATION (with login-specific rate limiting)
    // --------------------------------------------------------
    $router->group(['prefix' => '/auth'], function (Router $router) {

        // Login endpoint — strict rate limit (5 attempts / 5 min)
        $router->post('/login',           [AuthController::class, 'login']);
        $router->post('/register',        [AuthController::class, 'register']);
        $router->post('/refresh',         [AuthController::class, 'refresh']);
        $router->post('/forgot-password', [AuthController::class, 'forgotPassword']);
        $router->post('/reset-password',  [AuthController::class, 'resetPassword']);
        $router->get('/verify-email',     [AuthController::class, 'verifyEmail']);
    });

    // --------------------------------------------------------
    // TRIPS — PUBLIC (rate-limited)
    // --------------------------------------------------------
    $router->group(['prefix' => '/trips'], function (Router $router) {

        $router->get('',               [TripController::class, 'index']);
        $router->get('/featured',      [TripController::class, 'featured']);
        $router->get('/search',        [SearchController::class, 'search']);
        $router->get('/{id}',          [TripController::class, 'show']);
        $router->get('/slug/{slug}',   [TripController::class, 'showBySlug']);
    });

    // --------------------------------------------------------
    // CATEGORIES & DESTINATIONS — PUBLIC
    // --------------------------------------------------------
    $router->get('/categories',              [TripController::class, 'categories']);
    $router->get('/destinations',            [TripController::class, 'destinations']);
    $router->get('/destinations/popular',    [TripController::class, 'popularDestinations']);

    // ============================================================
    // API v1 — PROTECTED ROUTES (require JWT)
    // ============================================================
    $router->group(['middleware' => [AuthMiddleware::class]], function (Router $router) {

        // Authenticated auth actions
        $router->post('/auth/logout',     [AuthController::class, 'logout']);
        $router->post('/auth/logout-all', [AuthController::class, 'logoutAll']);

        // --------------------------------------------------------
        // CURRENT USER
        // --------------------------------------------------------
        $router->get('/me',                  [UserController::class, 'profile']);
        $router->patch('/me',                [UserController::class, 'updateProfile']);
        $router->post('/me/change-password', [UserController::class, 'changePassword']);

        // --------------------------------------------------------
        // BOOKINGS
        // --------------------------------------------------------
        $router->get('/bookings',            [BookingController::class, 'index']);
        $router->post('/bookings',           [BookingController::class, 'store']);
        $router->get('/bookings/{id}',       [BookingController::class, 'show']);
        $router->patch('/bookings/{id}/cancel', [BookingController::class, 'cancel']);
    });
});
