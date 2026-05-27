<?php

declare(strict_types=1);

namespace App\Infrastructure\Repositories;

use App\Domain\Contracts\Repositories\TripCategoryRepositoryInterface;
use App\Infrastructure\Database\Connection;

final class TripCategoryRepository implements TripCategoryRepositoryInterface
{
    public function __construct(private readonly Connection $db) {}

    public function findAll(): array
    {
        return $this->db->fetchAll(
            'SELECT c.*, COUNT(t.id) AS trip_count
             FROM trip_categories c
             LEFT JOIN trips t ON t.category_id = c.id AND t.status = "active" AND t.deleted_at IS NULL
             WHERE c.deleted_at IS NULL
             GROUP BY c.id
             ORDER BY c.sort_order ASC, c.name ASC'
        );
    }

    public function findById(int $id): ?array
    {
        return $this->db->fetchOne(
            'SELECT * FROM trip_categories WHERE id = :id AND deleted_at IS NULL',
            [':id' => $id]
        ) ?: null;
    }

    public function create(array $data): array
    {
        $id = $this->db->insert(
            'INSERT INTO trip_categories (name, slug, description, icon_url, sort_order, created_at, updated_at)
             VALUES (:name, :slug, :desc, :icon, :sort, NOW(), NOW())',
            [
                ':name' => $data['name'],
                ':slug' => $data['slug'],
                ':desc' => $data['description'] ?? null,
                ':icon' => $data['icon_url'] ?? null,
                ':sort' => $data['sort_order'] ?? 0,
            ]
        );
        return $this->findById($id);
    }

    public function update(int $id, array $data): bool
    {
        $allowed = ['name', 'slug', 'description', 'icon_url', 'sort_order'];
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
            'UPDATE trip_categories SET ' . implode(', ', $setClauses) . ' WHERE id = :id AND deleted_at IS NULL',
            $bindings
        ) > 0;
    }

    public function delete(int $id): bool
    {
        return $this->db->execute(
            'UPDATE trip_categories SET deleted_at = NOW() WHERE id = :id AND deleted_at IS NULL',
            [':id' => $id]
        ) > 0;
    }
}
