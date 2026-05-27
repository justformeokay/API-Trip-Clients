<?php

declare(strict_types=1);

namespace App\Presentation\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Domain\Contracts\Repositories\TripCategoryRepositoryInterface;
use App\Domain\Contracts\Repositories\DestinationRepositoryInterface;
use App\Helpers\ResponseHelper;
use App\Services\TripService;

/**
 * Public trip endpoints.
 */
final class TripController
{
    public function __construct(
        private readonly TripService                        $tripService,
        private readonly TripCategoryRepositoryInterface   $categoryRepository,
        private readonly DestinationRepositoryInterface    $destinationRepository
    ) {}

    /**
     * GET /api/v1/trips
     */
    public function index(Request $request): Response
    {
        $result = $this->tripService->getTrips($request->allQuery());
        return ResponseHelper::paginated($result, 'Trips fetched successfully.');
    }

    /**
     * GET /api/v1/trips/featured
     */
    public function featured(Request $request): Response
    {
        $limit  = min(20, max(1, (int) ($request->query('limit', 8))));
        $result = $this->tripService->getFeaturedTrips($limit);
        return ResponseHelper::success($result, 'Featured trips fetched successfully.');
    }

    /**
     * GET /api/v1/trips/{id}
     */
    public function show(Request $request): Response
    {
        $id   = (int) $request->param('id');
        $trip = $this->tripService->getTripById($id);
        return ResponseHelper::success($trip, 'Trip fetched successfully.');
    }

    /**
     * GET /api/v1/trips/slug/{slug}
     */
    public function showBySlug(Request $request): Response
    {
        $slug = (string) $request->param('slug');
        $trip = $this->tripService->getTripBySlug($slug);
        return ResponseHelper::success($trip, 'Trip fetched successfully.');
    }

    /**
     * GET /api/v1/categories
     */
    public function categories(Request $request): Response
    {
        $categories = $this->categoryRepository->findAll();
        return ResponseHelper::success($categories, 'Categories fetched successfully.');
    }

    /**
     * GET /api/v1/destinations
     */
    public function destinations(Request $request): Response
    {
        $page   = max(1, (int) $request->query('page', 1));
        $limit  = min(100, max(1, (int) $request->query('limit', 20)));
        $result = $this->destinationRepository->findAll($page, $limit);
        return ResponseHelper::paginated($result, 'Destinations fetched successfully.');
    }

    /**
     * GET /api/v1/destinations/popular
     */
    public function popularDestinations(Request $request): Response
    {
        $limit  = min(20, max(1, (int) $request->query('limit', 10)));
        $result = $this->destinationRepository->findPopular($limit);
        return ResponseHelper::success($result, 'Popular destinations fetched successfully.');
    }
}
