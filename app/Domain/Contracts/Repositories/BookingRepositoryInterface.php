<?php

declare(strict_types=1);

namespace App\Domain\Contracts\Repositories;

use App\Domain\Entities\Booking;

interface BookingRepositoryInterface
{
    public function findById(int $id): ?Booking;
    public function findByCode(string $code): ?Booking;

    /**
     * @return array{data: Booking[], total: int}
     */
    public function findByUserId(int $userId, int $page, int $limit): array;

    public function hasActiveBooking(int $userId, int $tripId): bool;
    public function create(array $data): Booking;
    public function updateStatus(int $id, string $status, ?string $cancelReason = null): bool;
}
