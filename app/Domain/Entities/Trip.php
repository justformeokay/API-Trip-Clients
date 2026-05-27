<?php

declare(strict_types=1);

namespace App\Domain\Entities;

/**
 * Trip domain entity.
 */
final class Trip
{
    public function __construct(
        public readonly ?int    $id,
        public readonly int     $organizerId,
        public readonly int     $categoryId,
        public readonly int     $destinationId,
        public readonly string  $title,
        public readonly string  $slug,
        public readonly string  $description,
        public readonly float   $price,
        public readonly ?float  $discountPrice,
        public readonly int     $totalSlots,
        public readonly int     $availableSlots,
        public readonly string  $departureDate,
        public readonly ?string $returnDate,
        public readonly int     $durationDays,
        public readonly string  $meetingPoint,
        public readonly float   $latitude,
        public readonly float   $longitude,
        public readonly string  $status,
        public readonly bool    $isFeatured,
        public readonly ?string $coverImage,
        public readonly array   $includes,
        public readonly array   $excludes,
        public readonly array   $itinerary,
        public readonly ?string $deletedAt,
        public readonly ?string $createdAt,
        public readonly ?string $updatedAt,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            id:             isset($data['id'])    ? (int) $data['id']   : null,
            organizerId:    (int) ($data['organizer_id']    ?? 0),
            categoryId:     (int) ($data['category_id']     ?? 0),
            destinationId:  (int) ($data['destination_id']  ?? 0),
            title:          $data['title']         ?? '',
            slug:           $data['slug']          ?? '',
            description:    $data['description']   ?? '',
            price:          (float) ($data['price']         ?? 0),
            discountPrice:  isset($data['discount_price']) ? (float) $data['discount_price'] : null,
            totalSlots:     (int) ($data['total_slots']     ?? 0),
            availableSlots: (int) ($data['available_slots'] ?? 0),
            departureDate:  $data['departure_date'] ?? '',
            returnDate:     $data['return_date']    ?? null,
            durationDays:   (int) ($data['duration_days']   ?? 1),
            meetingPoint:   $data['meeting_point']  ?? '',
            latitude:       (float) ($data['latitude']      ?? 0),
            longitude:      (float) ($data['longitude']     ?? 0),
            status:         $data['status']         ?? 'draft',
            isFeatured:     (bool) ($data['is_featured']    ?? false),
            coverImage:     $data['cover_image']    ?? null,
            includes:       is_array($data['includes']  ?? null) ? $data['includes']  : json_decode($data['includes']  ?? '[]', true) ?? [],
            excludes:       is_array($data['excludes']  ?? null) ? $data['excludes']  : json_decode($data['excludes']  ?? '[]', true) ?? [],
            itinerary:      is_array($data['itinerary'] ?? null) ? $data['itinerary'] : json_decode($data['itinerary'] ?? '[]', true) ?? [],
            deletedAt:      $data['deleted_at']     ?? null,
            createdAt:      $data['created_at']     ?? null,
            updatedAt:      $data['updated_at']     ?? null,
        );
    }

    public function isAvailable(): bool
    {
        return $this->status === 'active' && $this->availableSlots > 0 && $this->deletedAt === null;
    }

    public function getEffectivePrice(): float
    {
        return $this->discountPrice ?? $this->price;
    }

    public function toArray(): array
    {
        return [
            'id'               => $this->id,
            'organizer_id'     => $this->organizerId,
            'category_id'      => $this->categoryId,
            'destination_id'   => $this->destinationId,
            'title'            => $this->title,
            'slug'             => $this->slug,
            'description'      => $this->description,
            'price'            => $this->price,
            'discount_price'   => $this->discountPrice,
            'effective_price'  => $this->getEffectivePrice(),
            'total_slots'      => $this->totalSlots,
            'available_slots'  => $this->availableSlots,
            'departure_date'   => $this->departureDate,
            'return_date'      => $this->returnDate,
            'duration_days'    => $this->durationDays,
            'meeting_point'    => $this->meetingPoint,
            'latitude'         => $this->latitude,
            'longitude'        => $this->longitude,
            'status'           => $this->status,
            'is_featured'      => $this->isFeatured,
            'cover_image'      => $this->coverImage,
            'includes'         => $this->includes,
            'excludes'         => $this->excludes,
            'itinerary'        => $this->itinerary,
            'created_at'       => $this->createdAt,
            'updated_at'       => $this->updatedAt,
        ];
    }
}
