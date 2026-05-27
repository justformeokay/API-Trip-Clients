<?php

declare(strict_types=1);

return [
    'enabled'      => filter_var($_ENV['RATE_LIMIT_ENABLED'] ?? true, FILTER_VALIDATE_BOOLEAN),
    'requests'     => (int) ($_ENV['RATE_LIMIT_REQUESTS'] ?? 60),
    'window'       => (int) ($_ENV['RATE_LIMIT_WINDOW']   ?? 60),
    'login' => [
        'requests' => (int) ($_ENV['RATE_LIMIT_LOGIN_REQUESTS'] ?? 5),
        'window'   => (int) ($_ENV['RATE_LIMIT_LOGIN_WINDOW']   ?? 300),
    ],
];
