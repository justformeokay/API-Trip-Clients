<?php

declare(strict_types=1);

/**
 * Application bootstrap.
 * Initializes the Application, registers bindings, and loads routes.
 */

// Composer autoloader
require_once dirname(__DIR__) . '/vendor/autoload.php';

use App\Core\Application;

// Boot the application
$app = Application::getInstance(dirname(__DIR__))
    ->bootstrap();

// Load API routes into the router
$router = $app->getRouter();
require_once dirname(__DIR__) . '/routes/api.php';

return $app;
