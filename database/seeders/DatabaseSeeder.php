<?php

declare(strict_types=1);

/**
 * Database seeder for development/staging.
 * Run: php database/seeders/DatabaseSeeder.php
 *
 * This seeder creates:
 *  - 3 trip categories
 *  - 5 destinations
 *  - 1 admin user + 1 organizer user
 *  - 1 organizer record
 *  - 10 sample trips
 */

require_once dirname(__DIR__, 2) . '/bootstrap/app.php';

use App\Infrastructure\Database\Connection;
use App\Core\Application;

$app  = Application::getInstance();
$db   = $app->getContainer()->make(Connection::class);

echo "Seeding database...\n";

// ----------------------------------------------------------------
// CATEGORIES
// ----------------------------------------------------------------
$categories = [
    ['Hiking & Trekking',  'hiking-trekking',  'Mountain and trail hiking trips'],
    ['Beach & Island',     'beach-island',      'Coastal and island getaways'],
    ['Cultural Tour',      'cultural-tour',     'History and culture immersion trips'],
    ['Adventure Sports',   'adventure-sports',  'Extreme sports and adrenaline trips'],
    ['Wellness Retreat',   'wellness-retreat',  'Relaxation and healing experiences'],
];

$categoryIds = [];
foreach ($categories as [$name, $slug, $desc]) {
    $existing = $db->fetchOne('SELECT id FROM trip_categories WHERE slug = :slug', [':slug' => $slug]);
    if ($existing) {
        $categoryIds[$slug] = $existing['id'];
        continue;
    }
    $id = $db->insert(
        'INSERT INTO trip_categories (name, slug, description, sort_order, created_at, updated_at) VALUES (?, ?, ?, 0, NOW(), NOW())',
        [$name, $slug, $desc]
    );
    $categoryIds[$slug] = $id;
    echo "  Created category: {$name}\n";
}

// ----------------------------------------------------------------
// DESTINATIONS
// ----------------------------------------------------------------
$destinations = [
    ['Gunung Rinjani', 'Sembalun', 'Nusa Tenggara Barat', -8.4111,  116.4671],
    ['Raja Ampat',     'Waisai',   'Papua Barat',          -0.2314,  130.5208],
    ['Bali',           'Denpasar', 'Bali',                 -8.6705,  115.2126],
    ['Labuan Bajo',    'Komodo',   'Nusa Tenggara Timur',  -8.4960,  119.8823],
    ['Bromo',          'Probolinggo', 'Jawa Timur',        -7.9425,  112.9530],
];

$destIds = [];
foreach ($destinations as [$name, $city, $prov, $lat, $lng]) {
    $existing = $db->fetchOne('SELECT id FROM destinations WHERE name = :n AND city = :c', [':n' => $name, ':c' => $city]);
    if ($existing) {
        $destIds[$name] = $existing['id'];
        continue;
    }
    $id = $db->insert(
        'INSERT INTO destinations (name, city, province, latitude, longitude, created_at, updated_at)
         VALUES (?, ?, ?, ?, ?, NOW(), NOW())',
        [$name, $city, $prov, $lat, $lng]
    );
    $destIds[$name] = $id;
    echo "  Created destination: {$name}\n";
}

// ----------------------------------------------------------------
// ADMIN USER
// ----------------------------------------------------------------
$adminEmail = 'admin@healingyuk.com';
if (!$db->fetchOne('SELECT id FROM users WHERE email = :e', [':e' => $adminEmail])) {
    $db->insert(
        'INSERT INTO users (name, email, password, role, email_verified, is_active, email_verified_at, created_at, updated_at)
         VALUES (?, ?, ?, ?, 1, 1, NOW(), NOW(), NOW())',
        ['Admin HealingYuk', $adminEmail, password_hash('Admin@123456', PASSWORD_ARGON2ID), 'admin']
    );
    echo "  Created admin: {$adminEmail} / Admin@123456\n";
}

// ----------------------------------------------------------------
// ORGANIZER USER
// ----------------------------------------------------------------
$orgEmail = 'organizer@healingyuk.com';
$orgUserId = null;
$existing = $db->fetchOne('SELECT id FROM users WHERE email = :e', [':e' => $orgEmail]);
if (!$existing) {
    $orgUserId = $db->insert(
        'INSERT INTO users (name, email, password, role, email_verified, is_active, email_verified_at, created_at, updated_at)
         VALUES (?, ?, ?, ?, 1, 1, NOW(), NOW(), NOW())',
        ['Healing Organizer', $orgEmail, password_hash('Organizer@123456', PASSWORD_ARGON2ID), 'organizer']
    );
    echo "  Created organizer user: {$orgEmail} / Organizer@123456\n";
} else {
    $orgUserId = $existing['id'];
}

// ORGANIZER PROFILE
$existingOrg = $db->fetchOne('SELECT id FROM organizers WHERE user_id = :uid', [':uid' => $orgUserId]);
if (!$existingOrg) {
    $db->insert(
        'INSERT INTO organizers (user_id, business_name, description, phone, email, is_verified, created_at, updated_at)
         VALUES (?, ?, ?, ?, ?, 1, NOW(), NOW())',
        [$orgUserId, 'HealingYuk Adventure', 'Organizer resmi paket open trip Indonesia', '+62811000001', $orgEmail]
    );
    echo "  Created organizer profile\n";
}

$organizerId = $db->fetchOne('SELECT id FROM organizers WHERE user_id = :uid', [':uid' => $orgUserId])['id'];

// ----------------------------------------------------------------
// SAMPLE TRIPS
// ----------------------------------------------------------------
$trips = [
    [
        'title'         => 'Open Trip Rinjani 3D2N — Summit Attack',
        'slug'          => 'open-trip-rinjani-3d2n-summit-attack',
        'description'   => 'Pendakian Gunung Rinjani via Sembalun dengan program summit attack ke puncak 3726 mdpl.',
        'price'         => 1250000,
        'total_slots'   => 15,
        'departure_date'=> date('Y-m-d', strtotime('+14 days')) . ' 06:00:00',
        'duration_days' => 3,
        'meeting_point' => 'Sembalun, Lombok Timur',
        'category'      => 'hiking-trekking',
        'destination'   => 'Gunung Rinjani',
        'is_featured'   => 1,
    ],
    [
        'title'         => 'Island Hopping Raja Ampat 4D3N',
        'slug'          => 'island-hopping-raja-ampat-4d3n',
        'description'   => 'Eksplorasi keindahan bawah laut dan pulau-pulau eksotis di Raja Ampat Papua Barat.',
        'price'         => 3500000,
        'total_slots'   => 12,
        'departure_date'=> date('Y-m-d', strtotime('+21 days')) . ' 08:00:00',
        'duration_days' => 4,
        'meeting_point' => 'Bandara Domine Eduard Osok, Sorong',
        'category'      => 'beach-island',
        'destination'   => 'Raja Ampat',
        'is_featured'   => 1,
    ],
    [
        'title'         => 'Sunrise Bromo — Open Trip Akhir Pekan',
        'slug'          => 'sunrise-bromo-open-trip-akhir-pekan',
        'description'   => 'Saksikan matahari terbit di Gunung Bromo yang memukau. Paket 2D1N termasuk transport dari Surabaya.',
        'price'         => 450000,
        'total_slots'   => 20,
        'departure_date'=> date('Y-m-d', strtotime('+7 days')) . ' 22:00:00',
        'duration_days' => 2,
        'meeting_point' => 'Terminal Bungurasih, Surabaya',
        'category'      => 'adventure-sports',
        'destination'   => 'Bromo',
        'is_featured'   => 0,
    ],
    [
        'title'         => 'Komodo Dragon & Labuan Bajo 3D2N',
        'slug'          => 'komodo-dragon-labuan-bajo-3d2n',
        'description'   => 'Bertemu komodo di habitat aslinya, snorkeling di Pink Beach, dan sunset di Padar Island.',
        'price'         => 2800000,
        'total_slots'   => 10,
        'departure_date'=> date('Y-m-d', strtotime('+30 days')) . ' 07:00:00',
        'duration_days' => 3,
        'meeting_point' => 'Bandara Komodo, Labuan Bajo',
        'category'      => 'beach-island',
        'destination'   => 'Labuan Bajo',
        'is_featured'   => 1,
    ],
    [
        'title'         => 'Bali Wellness Retreat 3D2N',
        'slug'          => 'bali-wellness-retreat-3d2n',
        'description'   => 'Program healing & wellness di Ubud Bali. Yoga, meditasi, dan spa tradisional Bali.',
        'price'         => 1800000,
        'total_slots'   => 8,
        'departure_date'=> date('Y-m-d', strtotime('+10 days')) . ' 09:00:00',
        'duration_days' => 3,
        'meeting_point' => 'Bandara Ngurah Rai, Bali',
        'category'      => 'wellness-retreat',
        'destination'   => 'Bali',
        'is_featured'   => 0,
    ],
];

foreach ($trips as $trip) {
    if ($db->fetchOne('SELECT id FROM trips WHERE slug = :slug', [':slug' => $trip['slug']])) {
        continue;
    }

    $catId  = $categoryIds[$trip['category']] ?? $categoryIds['hiking-trekking'];
    $destId = $destIds[$trip['destination']]  ?? $destIds['Bromo'];

    $db->insert(
        'INSERT INTO trips
         (organizer_id, category_id, destination_id, title, slug, description, price,
          total_slots, available_slots, departure_date, duration_days, meeting_point,
          latitude, longitude, status, is_featured, created_at, updated_at)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, 0, "active", ?, NOW(), NOW())',
        [
            $organizerId, $catId, $destId,
            $trip['title'], $trip['slug'], $trip['description'],
            $trip['price'], $trip['total_slots'], $trip['total_slots'],
            $trip['departure_date'], $trip['duration_days'], $trip['meeting_point'],
            $trip['is_featured'],
        ]
    );
    echo "  Created trip: {$trip['title']}\n";
}

echo "\nSeeding completed successfully!\n";
echo "\nTest credentials:\n";
echo "  Admin:     admin@healingyuk.com      / Admin@123456\n";
echo "  Organizer: organizer@healingyuk.com  / Organizer@123456\n";
