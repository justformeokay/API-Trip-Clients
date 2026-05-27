<?php

declare(strict_types=1);

namespace App\Domain\Contracts\Repositories;

use App\Domain\Entities\Trip;

interface TripRepositoryInterface
{
    public function findById(int $id): ?Trip;
    public function findBySlug(string $slug): ?Trip;

    /**
     * Get paginated list of trips with optional filters.
     *
     * @return array{data: Trip[], total: int, page: int, limit: int}
     */
    public function findAll(array $filters, int $page, int $limit, string $sort, string $order): array;

    public function findFeatured(int $limit): array;

    /**
     * Radius search using Haversine formula.
     *
     * @return array{data: Trip[], total: int}
     */
    public function findByRadius(float $lat, float $lng, float $radiusKm, array $filters, int $page, int $limit): array;

    public function search(array $filters, int $page, int $limit): array;

    public function create(array $data): Trip;
    public function update(int $id, array $data): bool;
    public function delete(int $id): bool;
    public function decrementSlots(int $tripId, int $count): bool;
    public function incrementSlots(int $tripId, int $count): bool;
}
