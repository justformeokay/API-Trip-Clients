<?php

declare(strict_types=1);

// Ensure we're in test mode before loading anything
$_ENV['APP_ENV'] = 'testing';
putenv('APP_ENV=testing');

// Prevent any direct HTTP output during tests
if (!defined('HEALINGYUK_TESTING')) {
    define('HEALINGYUK_TESTING', true);
}

require_once dirname(__DIR__) . '/vendor/autoload.php';

// Load .env.testing if it exists, otherwise fall back to .env
$envFile = file_exists(dirname(__DIR__) . '/.env.testing') ? '.env.testing' : '.env';
$dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__), $envFile);
$dotenv->safeLoad();

// Override DB to use test database
$_ENV['DB_DATABASE'] = $_ENV['DB_DATABASE_TEST'] ?? ($_ENV['DB_DATABASE'] . '_test');
