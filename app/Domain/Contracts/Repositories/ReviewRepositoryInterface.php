<?php

declare(strict_types=1);

namespace App\Domain\Contracts\Repositories;

interface ReviewRepositoryInterface
{
    public function findByTripId(int $tripId, int $page, int $limit): array;
    public function findByUserId(int $userId, int $page, int $limit): array;
    public function create(array $data): array;
    public function hasReviewed(int $userId, int $tripId): bool;
    public function getAverageRating(int $tripId): float;
}
