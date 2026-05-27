<?php

declare(strict_types=1);

namespace App\Infrastructure\Repositories;

use App\Domain\Contracts\Repositories\SessionRepositoryInterface;
use App\Infrastructure\Database\Connection;

final class SessionRepository implements SessionRepositoryInterface
{
    public function __construct(private readonly Connection $db) {}

    public function create(array $data): array
    {
        $id = $this->db->insert(
            'INSERT INTO user_sessions
             (user_id, token_hash, device_name, ip_address, user_agent, expires_at, created_at)
             VALUES (:uid, :hash, :device, :ip, :ua, :exp, NOW())',
            [
                ':uid'    => $data['user_id'],
                ':hash'   => $data['token_hash'],
                ':device' => $data['device_name'] ?? null,
                ':ip'     => $data['ip_address']  ?? null,
                ':ua'     => $data['user_agent']  ?? null,
                ':exp'    => $data['expires_at'],
            ]
        );
        return $this->fetchById($id);
    }

    public function findByToken(string $tokenHash): ?array
    {
        return $this->db->fetchOne(
            'SELECT * FROM user_sessions
             WHERE token_hash = :hash AND expires_at > NOW() AND revoked_at IS NULL',
            [':hash' => $tokenHash]
        ) ?: null;
    }

    public function revokeByToken(string $tokenHash): bool
    {
        return $this->db->execute(
            'UPDATE user_sessions SET revoked_at = NOW() WHERE token_hash = :hash AND revoked_at IS NULL',
            [':hash' => $tokenHash]
        ) > 0;
    }

    public function revokeAllByUserId(int $userId): bool
    {
        return $this->db->execute(
            'UPDATE user_sessions SET revoked_at = NOW() WHERE user_id = :uid AND revoked_at IS NULL',
            [':uid' => $userId]
        ) > 0;
    }

    public function rotate(string $oldTokenHash, array $newData): array
    {
        $this->revokeByToken($oldTokenHash);
        return $this->create($newData);
    }

    public function purgeExpired(): int
    {
        return $this->db->execute(
            'DELETE FROM user_sessions WHERE expires_at < NOW() OR revoked_at IS NOT NULL'
        );
    }

    private function fetchById(int $id): array
    {
        return $this->db->fetchOne('SELECT * FROM user_sessions WHERE id = :id', [':id' => $id]) ?? [];
    }
}
