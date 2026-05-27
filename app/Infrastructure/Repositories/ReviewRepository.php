<?php

declare(strict_types=1);

namespace App\Infrastructure\Repositories;

use App\Domain\Contracts\Repositories\ReviewRepositoryInterface;
use App\Infrastructure\Database\Connection;

final class ReviewRepository implements ReviewRepositoryInterface
{
    public function __construct(private readonly Connection $db) {}

    public function findByTripId(int $tripId, int $page, int $limit): array
    {
        $offset = ($page - 1) * $limit;
        $data   = $this->db->fetchAll(
            'SELECT r.*, u.name AS user_name, u.avatar_url AS user_avatar
             FROM reviews r
             LEFT JOIN users u ON u.id = r.user_id
             WHERE r.trip_id = :tid AND r.deleted_at IS NULL
             ORDER BY r.created_at DESC LIMIT :l OFFSET :o',
            [':tid' => $tripId, ':l' => $limit, ':o' => $offset]
        );
        $total = (int) $this->db->fetchOne(
            'SELECT COUNT(*) AS cnt FROM reviews WHERE trip_id = :tid AND deleted_at IS NULL',
            [':tid' => $tripId]
        )['cnt'];
        return ['data' => $data, 'total' => $total, 'page' => $page, 'limit' => $limit];
    }

    public function findByUserId(int $userId, int $page, int $limit): array
    {
        $offset = ($page - 1) * $limit;
        $data   = $this->db->fetchAll(
            'SELECT r.*, t.title AS trip_title FROM reviews r
             LEFT JOIN trips t ON t.id = r.trip_id
             WHERE r.user_id = :uid AND r.deleted_at IS NULL
             ORDER BY r.created_at DESC LIMIT :l OFFSET :o',
            [':uid' => $userId, ':l' => $limit, ':o' => $offset]
        );
        $total = (int) $this->db->fetchOne(
            'SELECT COUNT(*) AS cnt FROM reviews WHERE user_id = :uid AND deleted_at IS NULL',
            [':uid' => $userId]
        )['cnt'];
        return ['data' => $data, 'total' => $total, 'page' => $page, 'limit' => $limit];
    }

    public function create(array $data): array
    {
        $id = $this->db->insert(
            'INSERT INTO reviews (user_id, trip_id, booking_id, rating, comment, created_at, updated_at)
             VALUES (:uid, :tid, :bid, :rating, :comment, NOW(), NOW())',
            [
                ':uid'     => $data['user_id'],
                ':tid'     => $data['trip_id'],
                ':bid'     => $data['booking_id'] ?? null,
                ':rating'  => $data['rating'],
                ':comment' => $data['comment'] ?? null,
            ]
        );
        return $this->db->fetchOne('SELECT * FROM reviews WHERE id = :id', [':id' => $id]) ?? [];
    }

    public function hasReviewed(int $userId, int $tripId): bool
    {
        $row = $this->db->fetchOne(
            'SELECT COUNT(*) AS cnt FROM reviews WHERE user_id = :uid AND trip_id = :tid AND deleted_at IS NULL',
            [':uid' => $userId, ':tid' => $tripId]
        );
        return ((int) ($row['cnt'] ?? 0)) > 0;
    }

    public function getAverageRating(int $tripId): float
    {
        $row = $this->db->fetchOne(
            'SELECT AVG(rating) AS avg_rating FROM reviews WHERE trip_id = :tid AND deleted_at IS NULL',
            [':tid' => $tripId]
        );
        return round((float) ($row['avg_rating'] ?? 0), 1);
    }
}
