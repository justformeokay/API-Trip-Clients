-- ============================================================
-- HealingYuk Database Schema — Migration 002
-- Organizers & Trip Catalog Tables
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;

-- ------------------------------------------------------------
-- ORGANIZERS
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `organizers` (
    `id`            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id`       BIGINT UNSIGNED NOT NULL,
    `business_name` VARCHAR(200)    NOT NULL,
    `description`   TEXT            NULL,
    `phone`         VARCHAR(20)     NULL,
    `email`         VARCHAR(255)    NULL,
    `website`       VARCHAR(500)    NULL,
    `logo_url`      VARCHAR(500)    NULL,
    `is_verified`   TINYINT(1)      NOT NULL DEFAULT 0,
    `verified_at`   DATETIME        NULL,
    `deleted_at`    DATETIME        NULL,
    `created_at`    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_organizers_user_id` (`user_id`),
    INDEX `idx_organizers_is_verified` (`is_verified`),
    INDEX `idx_organizers_deleted_at`  (`deleted_at`),
    CONSTRAINT `fk_organizers_user_id`
        FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- ORGANIZER DOCUMENTS (KTP, SIUP, etc.)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `organizer_documents` (
    `id`           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `organizer_id` BIGINT UNSIGNED NOT NULL,
    `type`         VARCHAR(50)     NOT NULL COMMENT 'ktp, siup, npwp, etc.',
    `file_url`     VARCHAR(500)    NOT NULL,
    `status`       ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
    `reviewed_at`  DATETIME        NULL,
    `created_at`   DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_od_organizer_id` (`organizer_id`),
    CONSTRAINT `fk_od_organizer_id`
        FOREIGN KEY (`organizer_id`) REFERENCES `organizers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- TRIP CATEGORIES
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `trip_categories` (
    `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name`        VARCHAR(100)    NOT NULL,
    `slug`        VARCHAR(120)    NOT NULL,
    `description` TEXT            NULL,
    `icon_url`    VARCHAR(500)    NULL,
    `sort_order`  SMALLINT        NOT NULL DEFAULT 0,
    `deleted_at`  DATETIME        NULL,
    `created_at`  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_categories_slug` (`slug`),
    INDEX `idx_categories_deleted_at` (`deleted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- DESTINATIONS
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `destinations` (
    `id`          BIGINT UNSIGNED  NOT NULL AUTO_INCREMENT,
    `name`        VARCHAR(200)     NOT NULL,
    `city`        VARCHAR(100)     NOT NULL,
    `province`    VARCHAR(100)     NOT NULL,
    `country`     VARCHAR(100)     NOT NULL DEFAULT 'Indonesia',
    `latitude`    DECIMAL(10, 7)   NOT NULL,
    `longitude`   DECIMAL(10, 7)   NOT NULL,
    `image_url`   VARCHAR(500)     NULL,
    `description` TEXT             NULL,
    `deleted_at`  DATETIME         NULL,
    `created_at`  DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`  DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_dest_city`       (`city`),
    INDEX `idx_dest_province`   (`province`),
    INDEX `idx_dest_coordinates`(`latitude`, `longitude`),
    INDEX `idx_dest_deleted_at` (`deleted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- TRIPS
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `trips` (
    `id`               BIGINT UNSIGNED  NOT NULL AUTO_INCREMENT,
    `organizer_id`     BIGINT UNSIGNED  NOT NULL,
    `category_id`      BIGINT UNSIGNED  NOT NULL,
    `destination_id`   BIGINT UNSIGNED  NOT NULL,
    `title`            VARCHAR(300)     NOT NULL,
    `slug`             VARCHAR(320)     NOT NULL,
    `description`      LONGTEXT         NOT NULL,
    `price`            DECIMAL(12, 2)   NOT NULL,
    `discount_price`   DECIMAL(12, 2)   NULL,
    `total_slots`      SMALLINT UNSIGNED NOT NULL DEFAULT 1,
    `available_slots`  SMALLINT UNSIGNED NOT NULL DEFAULT 1,
    `departure_date`   DATETIME         NOT NULL,
    `return_date`      DATETIME         NULL,
    `duration_days`    TINYINT UNSIGNED NOT NULL DEFAULT 1,
    `meeting_point`    VARCHAR(500)     NOT NULL,
    `latitude`         DECIMAL(10, 7)   NOT NULL,
    `longitude`        DECIMAL(10, 7)   NOT NULL,
    `status`           ENUM('draft','active','full','completed','cancelled') NOT NULL DEFAULT 'draft',
    `is_featured`      TINYINT(1)       NOT NULL DEFAULT 0,
    `cover_image`      VARCHAR(500)     NULL,
    `includes`         JSON             NULL,
    `excludes`         JSON             NULL,
    `itinerary`        JSON             NULL,
    `deleted_at`       DATETIME         NULL,
    `created_at`       DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`       DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY  `uq_trips_slug`           (`slug`),
    INDEX `idx_trips_organizer_id`        (`organizer_id`),
    INDEX `idx_trips_category_id`         (`category_id`),
    INDEX `idx_trips_destination_id`      (`destination_id`),
    INDEX `idx_trips_status`              (`status`),
    INDEX `idx_trips_is_featured`         (`is_featured`),
    INDEX `idx_trips_departure_date`      (`departure_date`),
    INDEX `idx_trips_price`               (`price`),
    INDEX `idx_trips_available_slots`     (`available_slots`),
    INDEX `idx_trips_coordinates`         (`latitude`, `longitude`),
    INDEX `idx_trips_deleted_at`          (`deleted_at`),
    FULLTEXT INDEX `ft_trips_search`      (`title`, `description`, `meeting_point`),
    CONSTRAINT `fk_trips_organizer_id`
        FOREIGN KEY (`organizer_id`) REFERENCES `organizers` (`id`),
    CONSTRAINT `fk_trips_category_id`
        FOREIGN KEY (`category_id`) REFERENCES `trip_categories` (`id`),
    CONSTRAINT `fk_trips_destination_id`
        FOREIGN KEY (`destination_id`) REFERENCES `destinations` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- TRIP IMAGES
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `trip_images` (
    `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `trip_id`    BIGINT UNSIGNED NOT NULL,
    `url`        VARCHAR(500)    NOT NULL,
    `alt_text`   VARCHAR(200)    NULL,
    `sort_order` TINYINT UNSIGNED NOT NULL DEFAULT 0,
    `created_at` DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_ti_trip_id` (`trip_id`, `sort_order`),
    CONSTRAINT `fk_ti_trip_id`
        FOREIGN KEY (`trip_id`) REFERENCES `trips` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
