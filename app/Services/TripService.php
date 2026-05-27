<?php

declare(strict_types=1);

namespace App\Services;

use App\Domain\Contracts\Repositories\TripRepositoryInterface;
use App\Exceptions\NotFoundException;
use App\Helpers\PaginationHelper;

/**
 * Trip business logic: listing, detail, featured, categories.
 */
final class TripService
{
    public function __construct(
        private readonly TripRepositoryInterface $tripRepository
    ) {}

    public function getTrips(array $queryParams): array
    {
        $page  = max(1, (int) ($queryParams['page']  ?? 1));
        $limit = min(
            (int) ($_ENV['PAGINATION_MAX_LIMIT'] ?? 100),
            max(1, (int) ($queryParams['limit'] ?? (int) ($_ENV['PAGINATION_DEFAULT_LIMIT'] ?? 15)))
        );
        $sort  = $queryParams['sort']  ?? 'created_at';
        $order = strtolower($queryParams['order'] ?? 'desc');

        $filters = $this->extractFilters($queryParams);

        $result = $this->tripRepository->findAll($filters, $page, $limit, $sort, $order);

        return PaginationHelper::format($result, $page, $limit);
    }

    public function getTripById(int $id): array
    {
        $trip = $this->tripRepository->findById($id);

        if (!$trip) {
            throw new NotFoundException("Trip with ID {$id} not found.");
        }

        return $trip->toArray();
    }

    public function getTripBySlug(string $slug): array
    {
        $trip = $this->tripRepository->findBySlug($slug);

        if (!$trip) {
            throw new NotFoundException("Trip '{$slug}' not found.");
        }

        return $trip->toArray();
    }

    public function getFeaturedTrips(int $limit = 8): array
    {
        return $this->tripRepository->findFeatured($limit);
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    private function extractFilters(array $params): array
    {
        $allowed = [
            'category_id', 'destination_id', 'destination', 'keyword',
            'departure_date', 'departure_from', 'departure_to',
            'price_min', 'price_max', 'min_slots', 'status',
        ];

        $filters = [];
        foreach ($allowed as $key) {
            if (isset($params[$key]) && $params[$key] !== '') {
                $filters[$key] = $params[$key];
            }
        }

        return $filters;
    }
}
