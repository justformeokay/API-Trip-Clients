<?php

declare(strict_types=1);

namespace App\Presentation\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Helpers\ResponseHelper;
use App\Services\SearchService;
use App\Validators\SearchValidator;

/**
 * Trip search controller (keyword, filters, and geo-radius).
 */
final class SearchController
{
    public function __construct(
        private readonly SearchService   $searchService,
        private readonly SearchValidator $validator
    ) {}

    /**
     * GET /api/v1/trips/search
     *
     * Query parameters:
     *   keyword, destination, category_id, destination_id,
     *   departure_date, departure_from, departure_to,
     *   price_min, price_max, min_slots,
     *   latitude, longitude, radius_km,
     *   page, limit
     */
    public function search(Request $request): Response
    {
        $params = $request->allQuery();
        $this->validator->validateSearch($params);

        $result = $this->searchService->search($params);

        return ResponseHelper::paginated($result, 'Search results fetched successfully.');
    }
}
