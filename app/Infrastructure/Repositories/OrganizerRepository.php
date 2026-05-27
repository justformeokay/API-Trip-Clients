<?php

declare(strict_types=1);

namespace App\Infrastructure\Repositories;

use App\Domain\Contracts\Repositories\OrganizerRepositoryInterface;
use App\Infrastructure\Database\Connection;

final class OrganizerRepository implements OrganizerRepositoryInterface
{
    public function __construct(private readonly Connection $db) {}

    public function findById(int $id): ?array
    {
        return $this->db->fetchOne(
            'SELECT * FROM organizers WHERE id = :id AND deleted_at IS NULL',
            [':id' => $id]
        ) ?: null;
    }

    public function findByUserId(int $userId): ?array
    {
        return $this->db->fetchOne(
            'SELECT * FROM organizers WHERE user_id = :uid AND deleted_at IS NULL',
            [':uid' => $userId]
        ) ?: null;
    }

    public function create(array $data): array
    {
        $id = $this->db->insert(
            'INSERT INTO organizers (user_id, business_name, description, phone, email, website, logo_url, created_at, updated_at)
             VALUES (:uid, :bname, :desc, :phone, :email, :website, :logo, NOW(), NOW())',
            [
                ':uid'     => $data['user_id'],
                ':bname'   => $data['business_name'],
                ':desc'    => $data['description'] ?? null,
                ':phone'   => $data['phone'] ?? null,
                ':email'   => $data['email'] ?? null,
                ':website' => $data['website'] ?? null,
                ':logo'    => $data['logo_url'] ?? null,
            ]
        );
        return $this->findById($id);
    }

    public function update(int $id, array $data): bool
    {
        $allowed = ['business_name', 'description', 'phone', 'email', 'website', 'logo_url', 'is_verified'];
        $setClauses = [];
        $bindings   = [':id' => $id];
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
        return $this->db->execute(
            'UPDATE organizers SET ' . implode(', ', $setClauses) . ' WHERE id = :id AND deleted_at IS NULL',
            $bindings
        ) > 0;
    }
}
