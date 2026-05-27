<?php

declare(strict_types=1);

return [
    'name'     => $_ENV['APP_NAME']    ?? 'HealingYuk API',
    'env'      => $_ENV['APP_ENV']     ?? 'production',
    'debug'    => filter_var($_ENV['APP_DEBUG'] ?? false, FILTER_VALIDATE_BOOLEAN),
    'url'      => $_ENV['APP_URL']     ?? 'http://localhost',
    'version'  => $_ENV['APP_VERSION'] ?? '1.0.0',
    'timezone' => $_ENV['APP_TIMEZONE'] ?? 'Asia/Jakarta',

    'pagination' => [
        'default_limit' => (int) ($_ENV['PAGINATION_DEFAULT_LIMIT'] ?? 15),
        'max_limit'     => (int) ($_ENV['PAGINATION_MAX_LIMIT'] ?? 100),
    ],
];
