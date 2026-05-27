<?php

declare(strict_types=1);

namespace App\Presentation\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Helpers\ResponseHelper;
use App\Services\BookingService;
use App\Validators\BookingValidator;

/**
 * Booking controller — all endpoints require authentication.
 */
final class BookingController
{
    public function __construct(
        private readonly BookingService   $bookingService,
        private readonly BookingValidator $validator
    ) {}

    /**
     * POST /api/v1/bookings
     */
    public function store(Request $request): Response
    {
        $data   = $request->all();
        $userId = (int) $request->getAttribute('user_id');

        $this->validator->validateCreate($data);

        $booking = $this->bookingService->createBooking($userId, $data);

        return ResponseHelper::created($booking, 'Booking created successfully.');
    }

    /**
     * GET /api/v1/bookings
     */
    public function index(Request $request): Response
    {
        $userId = (int) $request->getAttribute('user_id');
        $result = $this->bookingService->getUserBookings($userId, $request->allQuery());
        return ResponseHelper::paginated($result, 'Booking history fetched successfully.');
    }

    /**
     * GET /api/v1/bookings/{id}
     */
    public function show(Request $request): Response
    {
        $userId    = (int) $request->getAttribute('user_id');
        $bookingId = (int) $request->param('id');
        $booking   = $this->bookingService->getBookingById($bookingId, $userId);
        return ResponseHelper::success($booking, 'Booking fetched successfully.');
    }

    /**
     * PATCH /api/v1/bookings/{id}/cancel
     */
    public function cancel(Request $request): Response
    {
        $userId    = (int) $request->getAttribute('user_id');
        $bookingId = (int) $request->param('id');
        $data      = $request->all();

        $this->validator->validateCancel($data);

        $booking = $this->bookingService->cancelBooking(
            $bookingId,
            $userId,
            $data['reason'] ?? null
        );

        return ResponseHelper::success($booking, 'Booking cancelled successfully.');
    }
}
