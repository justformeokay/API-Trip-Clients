-- ============================================================
-- HealingYuk Database Schema — Migration 003
-- Bookings, Payments, Reviews & Audit Logs
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;

-- ------------------------------------------------------------
-- BOOKINGS
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `bookings` (
    `id`            BIGINT UNSIGNED  NOT NULL AUTO_INCREMENT,
    `booking_code`  VARCHAR(30)      NOT NULL COMMENT 'HYK-YYYYMMDD-XXXXXX',
    `user_id`       BIGINT UNSIGNED  NOT NULL,
    `trip_id`       BIGINT UNSIGNED  NOT NULL,
    `participants`  TINYINT UNSIGNED NOT NULL DEFAULT 1,
    `total_amount`  DECIMAL(12, 2)   NOT NULL,
    `status`        ENUM('pending','confirmed','cancelled','completed') NOT NULL DEFAULT 'pending',
    `cancel_reason` VARCHAR(500)     NULL,
    `cancelled_at`  DATETIME         NULL,
    `deleted_at`    DATETIME         NULL,
    `created_at`    DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`    DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_bookings_code`          (`booking_code`),
    INDEX `idx_bookings_user_id`           (`user_id`),
    INDEX `idx_bookings_trip_id`           (`trip_id`),
    INDEX `idx_bookings_status`            (`status`),
    INDEX `idx_bookings_created_at`        (`created_at`),
    INDEX `idx_bookings_deleted_at`        (`deleted_at`),
    CONSTRAINT `fk_bookings_user_id`
        FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
    CONSTRAINT `fk_bookings_trip_id`
        FOREIGN KEY (`trip_id`) REFERENCES `trips` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- BOOKING PARTICIPANTS (per-seat passenger details)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `booking_participants` (
    `id`            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `booking_id`    BIGINT UNSIGNED NOT NULL,
    `full_name`     VARCHAR(100)    NOT NULL,
    `id_number`     VARCHAR(30)     NULL COMMENT 'KTP/Passport number',
    `date_of_birth` DATE            NULL,
    `gender`        ENUM('male','female','other') NULL,
    `created_at`    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_bp_booking_id` (`booking_id`),
    CONSTRAINT `fk_bp_booking_id`
        FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- PAYMENTS
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `payments` (
    `id`             BIGINT UNSIGNED  NOT NULL AUTO_INCREMENT,
    `booking_id`     BIGINT UNSIGNED  NOT NULL,
    `amount`         DECIMAL(12, 2)   NOT NULL,
    `method`         VARCHAR(50)      NOT NULL COMMENT 'transfer, midtrans, etc.',
    `status`         ENUM('pending','paid','failed','refunded') NOT NULL DEFAULT 'pending',
    `gateway_ref`    VARCHAR(200)     NULL COMMENT 'External payment gateway reference',
    `gateway_payload`JSON             NULL,
    `paid_at`        DATETIME         NULL,
    `refunded_at`    DATETIME         NULL,
    `created_at`     DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`     DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_payments_booking_id`  (`booking_id`),
    INDEX `idx_payments_status`      (`status`),
    INDEX `idx_payments_gateway_ref` (`gateway_ref`),
    CONSTRAINT `fk_payments_booking_id`
        FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- REVIEWS
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `reviews` (
    `id`          BIGINT UNSIGNED  NOT NULL AUTO_INCREMENT,
    `user_id`     BIGINT UNSIGNED  NOT NULL,
    `trip_id`     BIGINT UNSIGNED  NOT NULL,
    `booking_id`  BIGINT UNSIGNED  NULL,
    `rating`      TINYINT UNSIGNED NOT NULL COMMENT '1–5',
    `comment`     TEXT             NULL,
    `deleted_at`  DATETIME         NULL,
    `created_at`  DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`  DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_reviews_user_trip` (`user_id`, `trip_id`),
    INDEX `idx_reviews_trip_id`    (`trip_id`),
    INDEX `idx_reviews_rating`     (`rating`),
    INDEX `idx_reviews_deleted_at` (`deleted_at`),
    CONSTRAINT `fk_reviews_user_id`
        FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
    CONSTRAINT `fk_reviews_trip_id`
        FOREIGN KEY (`trip_id`) REFERENCES `trips` (`id`),
    CONSTRAINT `fk_reviews_booking_id`
        FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE SET NULL,
    CONSTRAINT `chk_reviews_rating` CHECK (`rating` BETWEEN 1 AND 5)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- AUDIT LOGS
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `audit_logs` (
    `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `actor_id`    BIGINT UNSIGNED NULL COMMENT 'NULL = anonymous action',
    `action`      VARCHAR(100)    NOT NULL COMMENT 'e.g. auth.login, booking.created',
    `entity_type` VARCHAR(50)     NULL,
    `entity_id`   BIGINT          NULL,
    `ip_address`  VARCHAR(45)     NULL,
    `metadata`    JSON            NULL,
    `created_at`  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_al_actor_id`    (`actor_id`),
    INDEX `idx_al_action`      (`action`),
    INDEX `idx_al_entity`      (`entity_type`, `entity_id`),
    INDEX `idx_al_created_at`  (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
