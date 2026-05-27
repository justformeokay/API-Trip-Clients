<?php

declare(strict_types=1);

namespace App\Infrastructure\Repositories;

use App\Domain\Contracts\Repositories\UserRepositoryInterface;
use App\Domain\Entities\User;
use App\Infrastructure\Database\Connection;

final class UserRepository implements UserRepositoryInterface
{
    public function __construct(private readonly Connection $db) {}

    public function findById(int $id): ?User
    {
        $row = $this->db->fetchOne(
            'SELECT * FROM users WHERE id = :id AND deleted_at IS NULL',
            [':id' => $id]
        );
        return $row ? User::fromArray($row) : null;
    }

    public function findByEmail(string $email): ?User
    {
        $row = $this->db->fetchOne(
            'SELECT * FROM users WHERE email = :email AND deleted_at IS NULL',
            [':email' => strtolower(trim($email))]
        );
        return $row ? User::fromArray($row) : null;
    }

    public function findByVerificationToken(string $token): ?User
    {
        $row = $this->db->fetchOne(
            'SELECT u.* FROM users u
             INNER JOIN email_verifications ev ON ev.user_id = u.id
             WHERE ev.token = :token AND ev.expires_at > NOW() AND u.deleted_at IS NULL',
            [':token' => $token]
        );
        return $row ? User::fromArray($row) : null;
    }

    public function findByPasswordResetToken(string $token): ?User
    {
        $row = $this->db->fetchOne(
            'SELECT u.* FROM users u
             INNER JOIN password_resets pr ON pr.user_id = u.id
             WHERE pr.token = :token AND pr.expires_at > NOW() AND pr.used_at IS NULL AND u.deleted_at IS NULL',
            [':token' => $token]
        );
        return $row ? User::fromArray($row) : null;
    }

    public function create(array $data): User
    {
        $id = $this->db->insert(
            'INSERT INTO users (name, email, password, role, phone, is_active, created_at, updated_at)
             VALUES (:name, :email, :password, :role, :phone, 1, NOW(), NOW())',
            [
                ':name'     => $data['name'],
                ':email'    => strtolower(trim($data['email'])),
                ':password' => $data['password'],
                ':role'     => $data['role'] ?? 'user',
                ':phone'    => $data['phone'] ?? null,
            ]
        );
        return $this->findById($id);
    }

    public function update(int $id, array $data): bool
    {
        if (empty($data)) {
            return false;
        }

        $setClauses = [];
        $bindings   = [':id' => $id];

        $allowed = ['name', 'email', 'password', 'phone', 'avatar_url', 'is_active', 'email_verified', 'email_verified_at'];
        foreach ($allowed as $field) {
            if (array_key_exists($field, $data)) {
                $setClauses[] = "{$field} = :{$field}";
                $bindings[":{$field}"] = $data[$field];
            }
        }

        if (empty($setClauses)) {
            return false;
        }

        $setClauses[] = 'updated_at = NOW()';
        $sql = 'UPDATE users SET ' . implode(', ', $setClauses) . ' WHERE id = :id AND deleted_at IS NULL';

        return $this->db->execute($sql, $bindings) > 0;
    }

    public function delete(int $id): bool
    {
        return $this->db->execute(
            'UPDATE users SET deleted_at = NOW(), updated_at = NOW() WHERE id = :id AND deleted_at IS NULL',
            [':id' => $id]
        ) > 0;
    }
}
