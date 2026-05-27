<?php

declare(strict_types=1);

namespace App\Infrastructure\Repositories;

use App\Domain\Contracts\Repositories\DestinationRepositoryInterface;
use App\Infrastructure\Database\Connection;

final class DestinationRepository implements DestinationRepositoryInterface
{
    public function __construct(private readonly Connection $db) {}

    public function findById(int $id): ?array
    {
        return $this->db->fetchOne(
            'SELECT * FROM destinations WHERE id = :id AND deleted_at IS NULL',
            [':id' => $id]
        ) ?: null;
    }

    public function findAll(int $page, int $limit): array
    {
        $offset = ($page - 1) * $limit;
        $data   = $this->db->fetchAll(
            'SELECT * FROM destinations WHERE deleted_at IS NULL ORDER BY name ASC LIMIT :l OFFSET :o',
            [':l' => $limit, ':o' => $offset]
        );
        $total = (int) $this->db->fetchOne(
            'SELECT COUNT(*) AS cnt FROM destinations WHERE deleted_at IS NULL'
        )['cnt'];
        return ['data' => $data, 'total' => $total, 'page' => $page, 'limit' => $limit];
    }

    public function findPopular(int $limit): array
    {
        return $this->db->fetchAll(
            'SELECT d.*, COUNT(t.id) AS trip_count
             FROM destinations d
             LEFT JOIN trips t ON t.destination_id = d.id AND t.status = "active" AND t.deleted_at IS NULL
             WHERE d.deleted_at IS NULL
             GROUP BY d.id
             ORDER BY trip_count DESC
             LIMIT :l',
            [':l' => $limit]
        );
    }

    public function create(array $data): array
    {
        $id = $this->db->insert(
            'INSERT INTO destinations (name, city, province, country, latitude, longitude, image_url, description, created_at, updated_at)
             VALUES (:name, :city, :province, :country, :lat, :lng, :img, :desc, NOW(), NOW())',
            [
                ':name'     => $data['name'],
                ':city'     => $data['city'],
                ':province' => $data['province'],
                ':country'  => $data['country'] ?? 'Indonesia',
                ':lat'      => $data['latitude'],
                ':lng'      => $data['longitude'],
                ':img'      => $data['image_url'] ?? null,
                ':desc'     => $data['description'] ?? null,
            ]
        );
        return $this->findById($id);
    }

    public function update(int $id, array $data): bool
    {
        $allowed = ['name', 'city', 'province', 'country', 'latitude', 'longitude', 'image_url', 'description'];
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
            'UPDATE destinations SET ' . implode(', ', $setClauses) . ' WHERE id = :id AND deleted_at IS NULL',
            $bindings
        ) > 0;
    }
}
