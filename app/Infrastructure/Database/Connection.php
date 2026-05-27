<?php

declare(strict_types=1);

namespace App\Infrastructure\Database;

use App\Exceptions\DatabaseException;
use PDO;
use PDOException;

/**
 * Thread-safe PDO connection wrapper with singleton per config hash.
 */
final class Connection
{
    private static array $instances = [];
    private PDO $pdo;

    public function __construct(array $config)
    {
        $key = md5(serialize($config));

        if (!isset(self::$instances[$key])) {
            self::$instances[$key] = $this->createPdo($config);
        }

        $this->pdo = self::$instances[$key];
    }

    public function getPdo(): PDO
    {
        return $this->pdo;
    }

    /**
     * Execute a prepared statement and return all rows.
     *
     * @throws DatabaseException
     */
    public function fetchAll(string $sql, array $bindings = []): array
    {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($bindings);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            throw new DatabaseException('Query failed: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Execute a prepared statement and return a single row.
     *
     * @throws DatabaseException
     */
    public function fetchOne(string $sql, array $bindings = []): ?array
    {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($bindings);
            $result = $stmt->fetch();
            return $result !== false ? $result : null;
        } catch (PDOException $e) {
            throw new DatabaseException('Query failed: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Execute an INSERT/UPDATE/DELETE and return affected row count.
     *
     * @throws DatabaseException
     */
    public function execute(string $sql, array $bindings = []): int
    {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($bindings);
            return $stmt->rowCount();
        } catch (PDOException $e) {
            throw new DatabaseException('Execute failed: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Execute INSERT and return last inserted ID.
     *
     * @throws DatabaseException
     */
    public function insert(string $sql, array $bindings = []): int
    {
        $this->execute($sql, $bindings);
        return (int) $this->pdo->lastInsertId();
    }

    public function beginTransaction(): void
    {
        $this->pdo->beginTransaction();
    }

    public function commit(): void
    {
        $this->pdo->commit();
    }

    public function rollback(): void
    {
        if ($this->pdo->inTransaction()) {
            $this->pdo->rollBack();
        }
    }

    /**
     * Execute a callable within a database transaction.
     * Automatically rolls back on any exception.
     */
    public function transaction(callable $callback): mixed
    {
        $this->beginTransaction();
        try {
            $result = $callback($this);
            $this->commit();
            return $result;
        } catch (\Throwable $e) {
            $this->rollback();
            throw $e;
        }
    }

    // -------------------------------------------------------------------------
    // Private
    // -------------------------------------------------------------------------

    private function createPdo(array $config): PDO
    {
        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            $config['host']    ?? '127.0.0.1',
            $config['port']    ?? 3306,
            $config['database'] ?? '',
            $config['charset']  ?? 'utf8mb4'
        );

        try {
            return new PDO(
                $dsn,
                $config['username'] ?? 'root',
                $config['password'] ?? '',
                $config['options']  ?? [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ]
            );
        } catch (PDOException $e) {
            throw new DatabaseException('Database connection failed: ' . $e->getMessage(), 0, $e);
        }
    }
}
