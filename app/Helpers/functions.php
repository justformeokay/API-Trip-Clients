<?php

declare(strict_types=1);

if (!function_exists('config')) {
    /**
     * Global config helper – reads from the Application singleton's Config.
     */
    function config(string $key, mixed $default = null): mixed
    {
        try {
            $app = \App\Core\Application::getInstance();
            return $app->getContainer()->make('config')->get($key, $default);
        } catch (\Throwable) {
            return $default;
        }
    }
}

if (!function_exists('env')) {
    /**
     * Get an environment variable with an optional default.
     */
    function env(string $key, mixed $default = null): mixed
    {
        return $_ENV[$key] ?? $_SERVER[$key] ?? $default;
    }
}
