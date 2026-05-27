<?php

declare(strict_types=1);

namespace App\Infrastructure\Repositories;

use App\Domain\Contracts\Repositories\BookingRepositoryInterface;
use App\Domain\Entities\Booking;
use App\Infrastructure\Database\Connection;

final class BookingRepository implements BookingRepositoryInterface
{
    public function __construct(private readonly Connection $db) {}

    public function findById(int $id): ?Booking
    {
        $row = $this->db->fetchOne(
            'SELECT b.*,
                    t.title         AS trip_title,
                    t.departure_date,
                    t.meeting_point,
                    t.cover_image   AS trip_image
             FROM bookings b
             LEFT JOIN trips t ON t.id = b.trip_id
             WHERE b.id = :id AND b.deleted_at IS NULL',
            [':id' => $id]
        );
        return $row ? Booking::fromArray($row) : null;
    }

    public function findByCode(string $code): ?Booking
    {
        $row = $this->db->fetchOne(
            'SELECT b.*,
                    t.title         AS trip_title,
                    t.departure_date,
                    t.meeting_point,
                    t.cover_image   AS trip_image
             FROM bookings b
             LEFT JOIN trips t ON t.id = b.trip_id
             WHERE b.booking_code = :code AND b.deleted_at IS NULL',
            [':code' => $code]
        );
        return $row ? Booking::fromArray($row) : null;
    }

    public function findByUserId(int $userId, int $page, int $limit): array
    {
        $offset = ($page - 1) * $limit;

        $data = $this->db->fetchAll(
            'SELECT b.*,
                    t.title        AS trip_title,
                    t.departure_date,
                    t.meeting_point,
                    t.cover_image  AS trip_image
             FROM bookings b
             LEFT JOIN trips t ON t.id = b.trip_id
             WHERE b.user_id = :uid AND b.deleted_at IS NULL
             ORDER BY b.created_at DESC
             LIMIT :limit OFFSET :offset',
            [':uid' => $userId, ':limit' => $limit, ':offset' => $offset]
        );

        $total = (int) $this->db->fetchOne(
            'SELECT COUNT(*) AS cnt FROM bookings WHERE user_id = :uid AND deleted_at IS NULL',
            [':uid' => $userId]
        )['cnt'];

        return [
            'data'  => array_map(fn($r) => Booking::fromArray($r)->toArray(), $data),
            'total' => $total,
            'page'  => $page,
            'limit' => $limit,
        ];
    }

    public function hasActiveBooking(int $userId, int $tripId): bool
    {
        $row = $this->db->fetchOne(
            "SELECT COUNT(*) AS cnt FROM bookings
             WHERE user_id = :uid AND trip_id = :tid
               AND status NOT IN ('cancelled')
               AND deleted_at IS NULL",
            [':uid' => $userId, ':tid' => $tripId]
        );
        return ((int) ($row['cnt'] ?? 0)) > 0;
    }

    public function create(array $data): Booking
    {
        $id = $this->db->insert(
            'INSERT INTO bookings
             (booking_code, user_id, trip_id, participants, total_amount, status, created_at, updated_at)
             VALUES (:code, :uid, :tid, :participants, :amount, :status, NOW(), NOW())',
            [
                ':code'         => $data['booking_code'],
                ':uid'          => $data['user_id'],
                ':tid'          => $data['trip_id'],
                ':participants' => $data['participants'],
                ':amount'       => $data['total_amount'],
                ':status'       => $data['status'] ?? Booking::STATUS_PENDING,
            ]
        );
        return $this->findById($id);
    }

    public function updateStatus(int $id, string $status, ?string $cancelReason = null): bool
    {
        $sql = 'UPDATE bookings SET status = :status, updated_at = NOW()';
        $bindings = [':status' => $status, ':id' => $id];

        if ($status === Booking::STATUS_CANCELLED) {
            $sql .= ', cancel_reason = :reason, cancelled_at = NOW()';
            $bindings[':reason'] = $cancelReason;
        }

        $sql .= ' WHERE id = :id AND deleted_at IS NULL';
        return $this->db->execute($sql, $bindings) > 0;
    }
}
