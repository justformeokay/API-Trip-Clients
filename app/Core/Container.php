<?php

declare(strict_types=1);

namespace App\Core;

use Closure;
use App\Exceptions\NotFoundException;

/**
 * PSR-11 inspired Dependency Injection Container.
 * Supports singleton, binding, and auto-resolution via reflection.
 */
final class Container
{
    /** @var array<string, Closure> */
    private array $bindings = [];

    /** @var array<string, mixed> */
    private array $instances = [];

    /**
     * Register a binding (new instance each resolution).
     */
    public function bind(string $abstract, string|Closure $concrete): void
    {
        if (is_string($concrete)) {
            $this->bindings[$abstract] = fn(Container $c) => $c->build($concrete);
        } else {
            $this->bindings[$abstract] = $concrete;
        }
    }

    /**
     * Register a singleton (shared instance).
     */
    public function singleton(string $abstract, string|Closure $concrete): void
    {
        $this->bindings[$abstract] = function (Container $c) use ($abstract, $concrete) {
            if (!isset($this->instances[$abstract])) {
                $this->instances[$abstract] = is_string($concrete)
                    ? $c->build($concrete)
                    : $concrete($c);
            }
            return $this->instances[$abstract];
        };
    }

    /**
     * Register an already-instantiated object.
     */
    public function instance(string $abstract, mixed $instance): void
    {
        $this->instances[$abstract] = $instance;
    }

    /**
     * Resolve an abstract type from the container.
     */
    public function make(string $abstract): mixed
    {
        if (isset($this->instances[$abstract])) {
            return $this->instances[$abstract];
        }

        if (isset($this->bindings[$abstract])) {
            return ($this->bindings[$abstract])($this);
        }

        // Auto-resolution: attempt to build the class directly
        return $this->build($abstract);
    }

    /**
     * Auto-resolve a concrete class using reflection.
     *
     * @throws NotFoundException
     */
    public function build(string $concrete): mixed
    {
        if (!class_exists($concrete)) {
            throw new NotFoundException("Class [{$concrete}] not found in container.");
        }

        $reflector = new \ReflectionClass($concrete);

        if (!$reflector->isInstantiable()) {
            throw new NotFoundException("Class [{$concrete}] is not instantiable.");
        }

        $constructor = $reflector->getConstructor();

        if ($constructor === null) {
            return new $concrete();
        }

        $dependencies = array_map(
            function (\ReflectionParameter $param) use ($concrete) {
                $type = $param->getType();

                if ($type instanceof \ReflectionNamedType && !$type->isBuiltin()) {
                    return $this->make($type->getName());
                }

                if ($param->isDefaultValueAvailable()) {
                    return $param->getDefaultValue();
                }

                throw new NotFoundException(
                    "Cannot resolve parameter [{$param->getName()}] for [{$concrete}]."
                );
            },
            $constructor->getParameters()
        );

        return $reflector->newInstanceArgs($dependencies);
    }

    public function has(string $abstract): bool
    {
        return isset($this->bindings[$abstract]) || isset($this->instances[$abstract]);
    }
}
