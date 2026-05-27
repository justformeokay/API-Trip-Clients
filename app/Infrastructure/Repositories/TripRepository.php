<?php

declare(strict_types=1);

namespace App\Infrastructure\Repositories;

use App\Domain\Contracts\Repositories\TripRepositoryInterface;
use App\Domain\Entities\Trip;
use App\Infrastructure\Database\Connection;

final class TripRepository implements TripRepositoryInterface
{
    private const BASE_SELECT = '
        SELECT t.*,
               tc.name  AS category_name,
               d.name   AS destination_name,
               d.city   AS destination_city,
               d.province AS destination_province,
               o.business_name AS organizer_name,
               (SELECT GROUP_CONCAT(url ORDER BY sort_order) FROM trip_images WHERE trip_id = t.id)
                   AS image_urls
        FROM trips t
        LEFT JOIN trip_categories tc ON tc.id = t.category_id
        LEFT JOIN destinations     d  ON d.id  = t.destination_id
        LEFT JOIN organizers       o  ON o.id  = t.organizer_id
    ';

    public function __construct(private readonly Connection $db) {}

    public function findById(int $id): ?Trip
    {
        $row = $this->db->fetchOne(
            self::BASE_SELECT . ' WHERE t.id = :id AND t.deleted_at IS NULL',
            [':id' => $id]
        );
        return $row ? Trip::fromArray($row) : null;
    }

    public function findBySlug(string $slug): ?Trip
    {
        $row = $this->db->fetchOne(
            self::BASE_SELECT . ' WHERE t.slug = :slug AND t.deleted_at IS NULL',
            [':slug' => $slug]
        );
        return $row ? Trip::fromArray($row) : null;
    }

    public function findAll(array $filters, int $page, int $limit, string $sort, string $order): array
    {
        [$where, $bindings] = $this->buildFilters($filters);

        $allowedSorts  = ['price', 'departure_date', 'created_at', 'available_slots', 'title'];
        $allowedOrders = ['asc', 'desc'];
        $sort  = in_array($sort,  $allowedSorts,  true) ? "t.{$sort}"  : 't.created_at';
        $order = in_array($order, $allowedOrders, true) ? $order : 'desc';

        $offset = ($page - 1) * $limit;

        $data = $this->db->fetchAll(
            self::BASE_SELECT . $where . " ORDER BY {$sort} {$order} LIMIT :limit OFFSET :offset",
            array_merge($bindings, [':limit' => $limit, ':offset' => $offset])
        );

        $total = (int) $this->db->fetchOne(
            'SELECT COUNT(*) AS cnt FROM trips t' . $where,
            $bindings
        )['cnt'];

        return [
            'data'  => array_map(fn($r) => Trip::fromArray($r)->toArray(), $data),
            'total' => $total,
            'page'  => $page,
            'limit' => $limit,
        ];
    }

    public function findFeatured(int $limit): array
    {
        $rows = $this->db->fetchAll(
            self::BASE_SELECT . ' WHERE t.is_featured = 1 AND t.status = :status AND t.deleted_at IS NULL
            ORDER BY t.created_at DESC LIMIT :limit',
            [':status' => 'active', ':limit' => $limit]
        );
        return array_map(fn($r) => Trip::fromArray($r)->toArray(), $rows);
    }

    /**
     * Haversine radius search — optimized with bounding box pre-filter.
     */
    public function findByRadius(float $lat, float $lng, float $radiusKm, array $filters, int $page, int $limit): array
    {
        // Bounding box to reduce full-table scan before Haversine
        $latDelta = $radiusKm / 111.0;
        $lngDelta = $radiusKm / (111.0 * cos(deg2rad($lat)));

        [$where, $bindings] = $this->buildFilters(array_merge($filters, [
            '_lat_min' => $lat - $latDelta,
            '_lat_max' => $lat + $latDelta,
            '_lng_min' => $lng - $lngDelta,
            '_lng_max' => $lng + $lngDelta,
        ]));

        $distanceSql = '(6371 * ACOS(
            COS(RADIANS(:h_lat)) * COS(RADIANS(t.latitude))
            * COS(RADIANS(t.longitude) - RADIANS(:h_lng))
            + SIN(RADIANS(:h_lat)) * SIN(RADIANS(t.latitude))
        ))';

        $bindings[':h_lat']    = $lat;
        $bindings[':h_lng']    = $lng;
        $bindings[':h_lat2']   = $lat;
        $bindings[':h_lng2']   = $lng;
        $bindings[':radius']   = $radiusKm;
        $bindings[':limit']    = $limit;
        $bindings[':offset']   = ($page - 1) * $limit;

        $sql = self::BASE_SELECT . $where
            . " HAVING distance_km <= :radius ORDER BY distance_km ASC LIMIT :limit OFFSET :offset";

        // Inject distance alias into SELECT
        $sql = str_replace(
            'FROM trips t',
            ", {$distanceSql} AS distance_km FROM trips t",
            $sql
        );

        // Replace the HAVING bindings params (need unique names)
        $sql = str_replace(':h_lat)', ':h_lat2)', $sql);

        $data  = $this->db->fetchAll($sql, $bindings);
        $total = count($data); // approximate for radius queries

        return [
            'data'  => array_map(fn($r) => Trip::fromArray($r)->toArray(), $data),
            'total' => $total,
            'page'  => $page,
            'limit' => $limit,
        ];
    }

    public function search(array $filters, int $page, int $limit): array
    {
        return $this->findAll($filters, $page, $limit, 'created_at', 'desc');
    }

    public function create(array $data): Trip
    {
        $id = $this->db->insert(
            'INSERT INTO trips
             (organizer_id, category_id, destination_id, title, slug, description, price,
              discount_price, total_slots, available_slots, departure_date, return_date,
              duration_days, meeting_point, latitude, longitude, status, is_featured,
              cover_image, includes, excludes, itinerary, created_at, updated_at)
             VALUES
             (:organizer_id, :category_id, :destination_id, :title, :slug, :description, :price,
              :discount_price, :total_slots, :available_slots, :departure_date, :return_date,
              :duration_days, :meeting_point, :latitude, :longitude, :status, :is_featured,
              :cover_image, :includes, :excludes, :itinerary, NOW(), NOW())',
            [
                ':organizer_id'    => $data['organizer_id'],
                ':category_id'     => $data['category_id'],
                ':destination_id'  => $data['destination_id'],
                ':title'           => $data['title'],
                ':slug'            => $data['slug'],
                ':description'     => $data['description'],
                ':price'           => $data['price'],
                ':discount_price'  => $data['discount_price'] ?? null,
                ':total_slots'     => $data['total_slots'],
                ':available_slots' => $data['total_slots'],
                ':departure_date'  => $data['departure_date'],
                ':return_date'     => $data['return_date'] ?? null,
                ':duration_days'   => $data['duration_days'],
                ':meeting_point'   => $data['meeting_point'],
                ':latitude'        => $data['latitude'],
                ':longitude'       => $data['longitude'],
                ':status'          => $data['status'] ?? 'draft',
                ':is_featured'     => $data['is_featured'] ?? 0,
                ':cover_image'     => $data['cover_image'] ?? null,
                ':includes'        => json_encode($data['includes'] ?? []),
                ':excludes'        => json_encode($data['excludes'] ?? []),
                ':itinerary'       => json_encode($data['itinerary'] ?? []),
            ]
        );
        return $this->findById($id);
    }

    public function update(int $id, array $data): bool
    {
        if (empty($data)) {
            return false;
        }
        $allowed = [
            'title', 'description', 'price', 'discount_price', 'total_slots',
            'available_slots', 'departure_date', 'return_date', 'duration_days',
            'meeting_point', 'latitude', 'longitude', 'status', 'is_featured',
            'cover_image', 'includes', 'excludes', 'itinerary', 'category_id',
        ];
        $setClauses = [];
        $bindings   = [':id' => $id];
        foreach ($allowed as $field) {
            if (array_key_exists($field, $data)) {
                $setClauses[] = "{$field} = :{$field}";
                $v = $data[$field];
                $bindings[":{$field}"] = is_array($v) ? json_encode($v) : $v;
            }
        }
        if (empty($setClauses)) {
            return false;
        }
        $setClauses[] = 'updated_at = NOW()';
        return $this->db->execute(
            'UPDATE trips SET ' . implode(', ', $setClauses) . ' WHERE id = :id AND deleted_at IS NULL',
            $bindings
        ) > 0;
    }

    public function delete(int $id): bool
    {
        return $this->db->execute(
            'UPDATE trips SET deleted_at = NOW() WHERE id = :id AND deleted_at IS NULL',
            [':id' => $id]
        ) > 0;
    }

    public function decrementSlots(int $tripId, int $count): bool
    {
        return $this->db->execute(
            'UPDATE trips SET available_slots = available_slots - :count, updated_at = NOW()
             WHERE id = :id AND available_slots >= :count2 AND deleted_at IS NULL',
            [':count' => $count, ':id' => $tripId, ':count2' => $count]
        ) > 0;
    }

    public function incrementSlots(int $tripId, int $count): bool
    {
        return $this->db->execute(
            'UPDATE trips SET available_slots = available_slots + :count, updated_at = NOW()
             WHERE id = :id AND deleted_at IS NULL',
            [':count' => $count, ':id' => $tripId]
        ) > 0;
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    /**
     * Build WHERE clause and bindings from filter array.
     *
     * @return array{0: string, 1: array}
     */
    private function buildFilters(array $filters): array
    {
        $conditions = ['t.deleted_at IS NULL'];
        $bindings   = [];

        if (!empty($filters['status'])) {
            $conditions[] = 't.status = :status';
            $bindings[':status'] = $filters['status'];
        } else {
            $conditions[] = 't.status = :status';
            $bindings[':status'] = 'active';
        }

        if (!empty($filters['category_id'])) {
            $conditions[] = 't.category_id = :category_id';
            $bindings[':category_id'] = (int) $filters['category_id'];
        }

        if (!empty($filters['destination_id'])) {
            $conditions[] = 't.destination_id = :destination_id';
            $bindings[':destination_id'] = (int) $filters['destination_id'];
        }

        if (!empty($filters['destination'])) {
            $conditions[] = '(d.name LIKE :dest OR d.city LIKE :dest2 OR d.province LIKE :dest3)';
            $bindings[':dest']  = '%' . $filters['destination'] . '%';
            $bindings[':dest2'] = '%' . $filters['destination'] . '%';
            $bindings[':dest3'] = '%' . $filters['destination'] . '%';
        }

        if (!empty($filters['keyword'])) {
            $conditions[] = '(t.title LIKE :kw OR t.description LIKE :kw2 OR t.meeting_point LIKE :kw3)';
            $bindings[':kw']  = '%' . $filters['keyword'] . '%';
            $bindings[':kw2'] = '%' . $filters['keyword'] . '%';
            $bindings[':kw3'] = '%' . $filters['keyword'] . '%';
        }

        if (!empty($filters['departure_date'])) {
            $conditions[] = 'DATE(t.departure_date) = :dep_date';
            $bindings[':dep_date'] = $filters['departure_date'];
        }

        if (!empty($filters['departure_from'])) {
            $conditions[] = 'DATE(t.departure_date) >= :dep_from';
            $bindings[':dep_from'] = $filters['departure_from'];
        }

        if (!empty($filters['departure_to'])) {
            $conditions[] = 'DATE(t.departure_date) <= :dep_to';
            $bindings[':dep_to'] = $filters['departure_to'];
        }

        if (isset($filters['price_min']) && $filters['price_min'] !== '') {
            $conditions[] = 'COALESCE(t.discount_price, t.price) >= :price_min';
            $bindings[':price_min'] = (float) $filters['price_min'];
        }

        if (isset($filters['price_max']) && $filters['price_max'] !== '') {
            $conditions[] = 'COALESCE(t.discount_price, t.price) <= :price_max';
            $bindings[':price_max'] = (float) $filters['price_max'];
        }

        if (isset($filters['min_slots']) && $filters['min_slots'] !== '') {
            $conditions[] = 't.available_slots >= :min_slots';
            $bindings[':min_slots'] = (int) $filters['min_slots'];
        }

        if (isset($filters['_lat_min'])) {
            $conditions[] = 't.latitude BETWEEN :lat_min AND :lat_max';
            $conditions[] = 't.longitude BETWEEN :lng_min AND :lng_max';
            $bindings[':lat_min'] = $filters['_lat_min'];
            $bindings[':lat_max'] = $filters['_lat_max'];
            $bindings[':lng_min'] = $filters['_lng_min'];
            $bindings[':lng_max'] = $filters['_lng_max'];
        }

        $where = ' WHERE ' . implode(' AND ', $conditions);
        return [$where, $bindings];
    }
}
