<?php

declare(strict_types=1);

/**
 * HealingYuk REST API — Front Controller
 *
 * All HTTP requests are routed through this file via .htaccess / nginx config.
 */

// Enforce HTTPS in production (via header check, actual redirect done at web server level)
if (
    ($_ENV['APP_ENV'] ?? 'production') === 'production' &&
    ($_SERVER['HTTPS'] ?? 'off') !== 'on' &&
    ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') !== 'https'
) {
    // In production this should be handled by the web server;
    // this is a defense-in-depth response.
    http_response_code(426);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'HTTPS is required.']);
    exit;
}

// Disable error display in production (errors are logged, not displayed)
ini_set('display_errors', '0');
ini_set('log_errors', '1');

// Prevent clickjacking early (before any output)
header_remove('X-Powered-By');

// Bootstrap and run
$app = require_once dirname(__DIR__) . '/bootstrap/app.php';
$app->run();
