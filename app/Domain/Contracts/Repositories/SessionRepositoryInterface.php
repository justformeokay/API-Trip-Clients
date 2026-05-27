<?php

declare(strict_types=1);

namespace App\Domain\Contracts\Repositories;

interface SessionRepositoryInterface
{
    /**
     * Store a new refresh token session.
     */
    public function create(array $data): array;

    /**
     * Find a session by refresh token hash.
     */
    public function findByToken(string $tokenHash): ?array;

    /**
     * Revoke (delete) a single session by token hash.
     */
    public function revokeByToken(string $tokenHash): bool;

    /**
     * Revoke all sessions for a user (logout everywhere).
     */
    public function revokeAllByUserId(int $userId): bool;

    /**
     * Rotate refresh token: revoke old, store new.
     */
    public function rotate(string $oldTokenHash, array $newData): array;

    /**
     * Remove expired sessions.
     */
    public function purgeExpired(): int;
}
