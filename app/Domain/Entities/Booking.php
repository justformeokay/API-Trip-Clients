<?php

declare(strict_types=1);

namespace App\Domain\Entities;

/**
 * Booking domain entity.
 */
final class Booking
{
    // Status constants
    public const STATUS_PENDING   = 'pending';
    public const STATUS_CONFIRMED = 'confirmed';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_COMPLETED = 'completed';

    public function __construct(
        public readonly ?int    $id,
        public readonly string  $bookingCode,
        public readonly int     $userId,
        public readonly int     $tripId,
        public readonly int     $participants,
        public readonly float   $totalAmount,
        public readonly string  $status,
        public readonly ?string $cancelReason,
        public readonly ?string $cancelledAt,
        public readonly ?string $deletedAt,
        public readonly ?string $createdAt,
        public readonly ?string $updatedAt,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            id:           isset($data['id'])   ? (int) $data['id']  : null,
            bookingCode:  $data['booking_code'] ?? '',
            userId:       (int) ($data['user_id']      ?? 0),
            tripId:       (int) ($data['trip_id']      ?? 0),
            participants: (int) ($data['participants']  ?? 1),
            totalAmount:  (float) ($data['total_amount'] ?? 0),
            status:       $data['status']        ?? self::STATUS_PENDING,
            cancelReason: $data['cancel_reason'] ?? null,
            cancelledAt:  $data['cancelled_at']  ?? null,
            deletedAt:    $data['deleted_at']    ?? null,
            createdAt:    $data['created_at']    ?? null,
            updatedAt:    $data['updated_at']    ?? null,
        );
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isCancellable(): bool
    {
        return in_array($this->status, [self::STATUS_PENDING, self::STATUS_CONFIRMED], true);
    }

    public function toArray(): array
    {
        return [
            'id'            => $this->id,
            'booking_code'  => $this->bookingCode,
            'user_id'       => $this->userId,
            'trip_id'       => $this->tripId,
            'participants'  => $this->participants,
            'total_amount'  => $this->totalAmount,
            'status'        => $this->status,
            'cancel_reason' => $this->cancelReason,
            'cancelled_at'  => $this->cancelledAt,
            'created_at'    => $this->createdAt,
            'updated_at'    => $this->updatedAt,
        ];
    }
}
