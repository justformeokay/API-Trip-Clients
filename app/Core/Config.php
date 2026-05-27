<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Loads PHP config files from a directory and provides dot-notation access.
 */
final class Config
{
    /** @var array<string, mixed> */
    private array $items = [];

    public function __construct(string $configPath)
    {
        foreach (glob($configPath . '/*.php') as $file) {
            $key = basename($file, '.php');
            $this->items[$key] = require $file;
        }
    }

    /**
     * Get a config value using dot notation.
     * e.g. get('database.host') or get('jwt')
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $parts = explode('.', $key, 2);
        $file  = $parts[0];

        if (!isset($this->items[$file])) {
            return $default;
        }

        if (count($parts) === 1) {
            return $this->items[$file];
        }

        return $this->dotGet($this->items[$file], $parts[1], $default);
    }

    public function all(): array
    {
        return $this->items;
    }

    private function dotGet(array $array, string $key, mixed $default): mixed
    {
        foreach (explode('.', $key) as $segment) {
            if (!is_array($array) || !array_key_exists($segment, $array)) {
                return $default;
            }
            $array = $array[$segment];
        }
        return $array;
    }
}
