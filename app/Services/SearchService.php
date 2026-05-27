<?php

declare(strict_types=1);

namespace App\Services;

use App\Domain\Contracts\Repositories\TripRepositoryInterface;
use App\Exceptions\ValidationException;
use App\Helpers\PaginationHelper;

/**
 * Trip search with support for:
 * - Keyword / destination text search
 * - Filters (date, price, slots, category)
 * - Geospatial radius search using Haversine formula
 */
final class SearchService
{
    private const MAX_RADIUS_KM = 500.0;

    public function __construct(
        private readonly TripRepositoryInterface $tripRepository
    ) {}

    /**
     * Comprehensive trip search with optional radius filter.
     */
    public function search(array $params): array
    {
        $page  = max(1, (int) ($params['page']  ?? 1));
        $limit = min(
            (int) ($_ENV['PAGINATION_MAX_LIMIT'] ?? 100),
            max(1, (int) ($params['limit'] ?? (int) ($_ENV['PAGINATION_DEFAULT_LIMIT'] ?? 15)))
        );

        // Geo-radius search path
        if (isset($params['latitude'], $params['longitude'])) {
            return $this->searchByRadius($params, $page, $limit);
        }

        // Standard search path
        $filters = $this->buildFilters($params);
        $result  = $this->tripRepository->search($filters, $page, $limit);

        return PaginationHelper::format($result, $page, $limit);
    }

    /**
     * Radius-based trip search using Haversine formula.
     */
    private function searchByRadius(array $params, int $page, int $limit): array
    {
        $lat      = (float) $params['latitude'];
        $lng      = (float) $params['longitude'];
        $radiusKm = (float) ($params['radius_km'] ?? 50);

        if (!$this->isValidLatitude($lat)) {
            throw new ValidationException(['latitude' => ['Latitude must be between -90 and 90.']]);
        }

        if (!$this->isValidLongitude($lng)) {
            throw new ValidationException(['longitude' => ['Longitude must be between -180 and 180.']]);
        }

        if ($radiusKm <= 0 || $radiusKm > self::MAX_RADIUS_KM) {
            throw new ValidationException([
                'radius_km' => ['Radius must be between 1 and ' . self::MAX_RADIUS_KM . ' km.'],
            ]);
        }

        $filters = $this->buildFilters($params);
        $result  = $this->tripRepository->findByRadius($lat, $lng, $radiusKm, $filters, $page, $limit);

        return array_merge(
            PaginationHelper::format($result, $page, $limit),
            ['search_center' => ['latitude' => $lat, 'longitude' => $lng, 'radius_km' => $radiusKm]]
        );
    }

    private function buildFilters(array $params): array
    {
        $allowed = [
            'keyword', 'destination', 'category_id', 'destination_id',
            'departure_date', 'departure_from', 'departure_to',
            'price_min', 'price_max', 'min_slots',
        ];

        $filters = [];
        foreach ($allowed as $key) {
            if (isset($params[$key]) && $params[$key] !== '') {
                $filters[$key] = $params[$key];
            }
        }

        return $filters;
    }

    private function isValidLatitude(float $lat): bool
    {
        return $lat >= -90.0 && $lat <= 90.0;
    }

    private function isValidLongitude(float $lng): bool
    {
        return $lng >= -180.0 && $lng <= 180.0;
    }
}
