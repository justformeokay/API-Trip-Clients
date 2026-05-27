<?php

declare(strict_types=1);

namespace App\Core;

use App\Exceptions\Handler;
use Throwable;

/**
 * Main Application bootstrap and lifecycle manager.
 */
final class Application
{
    private static ?self $instance = null;
    private Container $container;
    private Router $router;
    private string $basePath;

    private function __construct(string $basePath)
    {
        $this->basePath  = rtrim($basePath, '/');
        $this->container = new Container();
        $this->router    = new Router($this->container);
    }

    public static function getInstance(string $basePath = ''): self
    {
        if (self::$instance === null) {
            self::$instance = new self($basePath);
        }
        return self::$instance;
    }

    public function bootstrap(): self
    {
        $this->loadEnvironment();
        $this->setTimezone();
        $this->registerBindings();
        return $this;
    }

    public function run(): void
    {
        try {
            $request  = Request::capture();
            $response = $this->router->dispatch($request);
            $response->send();
        } catch (Throwable $e) {
            (new Handler())->render($e)->send();
        }
    }

    public function getContainer(): Container
    {
        return $this->container;
    }

    public function getRouter(): Router
    {
        return $this->router;
    }

    public function basePath(string $path = ''): string
    {
        return $this->basePath . ($path ? DIRECTORY_SEPARATOR . ltrim($path, '/') : '');
    }

    private function loadEnvironment(): void
    {
        $envFile = $this->basePath('.env');
        if (file_exists($envFile)) {
            $dotenv = \Dotenv\Dotenv::createImmutable($this->basePath);
            $dotenv->load();
        }
    }

    private function setTimezone(): void
    {
        date_default_timezone_set($_ENV['APP_TIMEZONE'] ?? 'Asia/Jakarta');
    }

    private function registerBindings(): void
    {
        // Register config into container
        $this->container->singleton('config', function () {
            return new Config($this->basePath('config'));
        });

        // Register DB connection
        $this->container->singleton(
            \App\Infrastructure\Database\Connection::class,
            function () {
                return new \App\Infrastructure\Database\Connection(
                    $this->container->make('config')->get('database')
                );
            }
        );

        // Register services
        $this->bindRepositories();
        $this->bindServices();
    }

    private function bindRepositories(): void
    {
        $bindings = [
            \App\Domain\Contracts\Repositories\UserRepositoryInterface::class
                => \App\Infrastructure\Repositories\UserRepository::class,
            \App\Domain\Contracts\Repositories\TripRepositoryInterface::class
                => \App\Infrastructure\Repositories\TripRepository::class,
            \App\Domain\Contracts\Repositories\BookingRepositoryInterface::class
                => \App\Infrastructure\Repositories\BookingRepository::class,
            \App\Domain\Contracts\Repositories\SessionRepositoryInterface::class
                => \App\Infrastructure\Repositories\SessionRepository::class,
            \App\Domain\Contracts\Repositories\OrganizerRepositoryInterface::class
                => \App\Infrastructure\Repositories\OrganizerRepository::class,
            \App\Domain\Contracts\Repositories\DestinationRepositoryInterface::class
                => \App\Infrastructure\Repositories\DestinationRepository::class,
            \App\Domain\Contracts\Repositories\TripCategoryRepositoryInterface::class
                => \App\Infrastructure\Repositories\TripCategoryRepository::class,
            \App\Domain\Contracts\Repositories\ReviewRepositoryInterface::class
                => \App\Infrastructure\Repositories\ReviewRepository::class,
        ];

        foreach ($bindings as $abstract => $concrete) {
            $this->container->bind($abstract, $concrete);
        }
    }

    private function bindServices(): void
    {
        $this->container->singleton(\App\Services\JwtService::class, function () {
            return new \App\Services\JwtService(
                $this->container->make('config')->get('jwt')
            );
        });

        $this->container->singleton(\App\Services\AuthService::class, function () {
            return new \App\Services\AuthService(
                $this->container->make(\App\Domain\Contracts\Repositories\UserRepositoryInterface::class),
                $this->container->make(\App\Domain\Contracts\Repositories\SessionRepositoryInterface::class),
                $this->container->make(\App\Services\JwtService::class),
                $this->container->make(\App\Services\AuditLogService::class)
            );
        });

        $this->container->singleton(\App\Services\TripService::class, function () {
            return new \App\Services\TripService(
                $this->container->make(\App\Domain\Contracts\Repositories\TripRepositoryInterface::class)
            );
        });

        $this->container->singleton(\App\Services\SearchService::class, function () {
            return new \App\Services\SearchService(
                $this->container->make(\App\Domain\Contracts\Repositories\TripRepositoryInterface::class)
            );
        });

        $this->container->singleton(\App\Services\BookingService::class, function () {
            return new \App\Services\BookingService(
                $this->container->make(\App\Domain\Contracts\Repositories\BookingRepositoryInterface::class),
                $this->container->make(\App\Domain\Contracts\Repositories\TripRepositoryInterface::class),
                $this->container->make(\App\Services\AuditLogService::class)
            );
        });

        $this->container->singleton(\App\Services\AuditLogService::class, function () {
            return new \App\Services\AuditLogService(
                $this->container->make(\App\Infrastructure\Database\Connection::class)
            );
        });
    }
}
