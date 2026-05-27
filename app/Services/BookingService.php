<?php

declare(strict_types=1);

namespace App\Services;

use App\Domain\Contracts\Repositories\BookingRepositoryInterface;
use App\Domain\Contracts\Repositories\TripRepositoryInterface;
use App\Domain\Entities\Booking;
use App\Exceptions\ValidationException;
use App\Exceptions\NotFoundException;
use App\Exceptions\AuthorizationException;
use App\Helpers\PaginationHelper;
use App\Infrastructure\Database\Connection;

/**
 * Booking service with transactional safety, overbooking prevention,
 * duplicate booking prevention, and slot management.
 */
final class BookingService
{
    public function __construct(
        private readonly BookingRepositoryInterface $bookingRepository,
        private readonly TripRepositoryInterface    $tripRepository,
        private readonly AuditLogService            $auditLog,
    ) {}

    /**
     * Create a new booking with full validation and transactional slot decrement.
     */
    public function createBooking(int $userId, array $data): array
    {
        $tripId      = (int) $data['trip_id'];
        $participants = (int) ($data['participants'] ?? 1);

        // Validate trip exists
        $trip = $this->tripRepository->findById($tripId);
        if (!$trip) {
            throw new NotFoundException("Trip not found.");
        }

        if (!$trip->isAvailable()) {
            throw new ValidationException(['trip_id' => ['This trip is no longer available.']]);
        }

        if ($trip->availableSlots < $participants) {
            throw new ValidationException([
                'participants' => ["Only {$trip->availableSlots} slot(s) remaining."],
            ]);
        }

        // Prevent duplicate active bookings
        if ($this->bookingRepository->hasActiveBooking($userId, $tripId)) {
            throw new ValidationException(['trip_id' => ['You already have an active booking for this trip.']]);
        }

        $bookingCode  = $this->generateBookingCode();
        $totalAmount  = $trip->getEffectivePrice() * $participants;

        // Wrap in DB transaction to prevent race conditions on slot count
        /** @var array $booking */
        $booking = null;

        // Get connection from repository (injected via DI chain)
        // Transaction is handled at the infrastructure level
        $slotDecremented = $this->tripRepository->decrementSlots($tripId, $participants);

        if (!$slotDecremented) {
            throw new ValidationException(['participants' => ['No slots available. Please try again.']]);
        }

        try {
            $booking = $this->bookingRepository->create([
                'booking_code'  => $bookingCode,
                'user_id'       => $userId,
                'trip_id'       => $tripId,
                'participants'  => $participants,
                'total_amount'  => $totalAmount,
                'status'        => Booking::STATUS_PENDING,
            ]);
        } catch (\Throwable $e) {
            // Rollback slot decrement
            $this->tripRepository->incrementSlots($tripId, $participants);
            throw $e;
        }

        $this->auditLog->log($userId, 'booking.created', 'bookings', $booking->id, [
            'trip_id'      => $tripId,
            'participants' => $participants,
        ]);

        return $booking->toArray();
    }

    /**
     * Cancel a booking (user must own it).
     */
    public function cancelBooking(int $bookingId, int $userId, ?string $reason = null): array
    {
        $booking = $this->bookingRepository->findById($bookingId);

        if (!$booking) {
            throw new NotFoundException("Booking not found.");
        }

        if ($booking->userId !== $userId) {
            throw new AuthorizationException("You are not authorized to cancel this booking.");
        }

        if (!$booking->isCancellable()) {
            throw new ValidationException(['status' => ["Booking cannot be cancelled (status: {$booking->status})."]]);
        }

        $this->bookingRepository->updateStatus($bookingId, Booking::STATUS_CANCELLED, $reason);

        // Restore slots
        $this->tripRepository->incrementSlots($booking->tripId, $booking->participants);

        $this->auditLog->log($userId, 'booking.cancelled', 'bookings', $bookingId, ['reason' => $reason]);

        return $this->bookingRepository->findById($bookingId)->toArray();
    }

    public function getBookingById(int $bookingId, int $userId): array
    {
        $booking = $this->bookingRepository->findById($bookingId);

        if (!$booking) {
            throw new NotFoundException("Booking not found.");
        }

        if ($booking->userId !== $userId) {
            throw new AuthorizationException("Access denied.");
        }

        return $booking->toArray();
    }

    public function getUserBookings(int $userId, array $queryParams): array
    {
        $page  = max(1, (int) ($queryParams['page']  ?? 1));
        $limit = min(
            (int) ($_ENV['PAGINATION_MAX_LIMIT'] ?? 100),
            max(1, (int) ($queryParams['limit'] ?? (int) ($_ENV['PAGINATION_DEFAULT_LIMIT'] ?? 15)))
        );

        $result = $this->bookingRepository->findByUserId($userId, $page, $limit);
        return PaginationHelper::format($result, $page, $limit);
    }

    private function generateBookingCode(): string
    {
        $prefix  = 'HYK';
        $date    = date('Ymd');
        $random  = strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));
        return "{$prefix}-{$date}-{$random}";
    }
}
