-- ============================================================
-- HealingYuk Database Schema — Migration 001
-- Users & Authentication Tables
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;
SET sql_mode = 'STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';

-- ------------------------------------------------------------
-- USERS
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
    `id`                BIGINT UNSIGNED  NOT NULL AUTO_INCREMENT,
    `name`              VARCHAR(100)     NOT NULL,
    `email`             VARCHAR(255)     NOT NULL,
    `password`          VARCHAR(255)     NOT NULL,
    `role`              ENUM('user','organizer','admin') NOT NULL DEFAULT 'user',
    `phone`             VARCHAR(20)      NULL,
    `avatar_url`        VARCHAR(500)     NULL,
    `email_verified`    TINYINT(1)       NOT NULL DEFAULT 0,
    `email_verified_at` DATETIME         NULL,
    `is_active`         TINYINT(1)       NOT NULL DEFAULT 1,
    `deleted_at`        DATETIME         NULL,
    `created_at`        DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`        DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_users_email` (`email`),
    INDEX `idx_users_role`      (`role`),
    INDEX `idx_users_is_active` (`is_active`),
    INDEX `idx_users_deleted_at`(`deleted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- USER SESSIONS (Refresh Tokens)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `user_sessions` (
    `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id`     BIGINT UNSIGNED NOT NULL,
    `token_hash`  CHAR(64)        NOT NULL COMMENT 'SHA-256 hash of the opaque refresh token',
    `device_name` VARCHAR(50)     NULL,
    `ip_address`  VARCHAR(45)     NULL COMMENT 'IPv4 or IPv6',
    `user_agent`  VARCHAR(500)    NULL,
    `revoked_at`  DATETIME        NULL,
    `expires_at`  DATETIME        NOT NULL,
    `created_at`  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_sessions_token_hash` (`token_hash`),
    INDEX `idx_sessions_user_id`   (`user_id`),
    INDEX `idx_sessions_expires_at`(`expires_at`),
    INDEX `idx_sessions_revoked_at`(`revoked_at`),
    CONSTRAINT `fk_sessions_user_id`
        FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- EMAIL VERIFICATIONS
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `email_verifications` (
    `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id`    BIGINT UNSIGNED NOT NULL,
    `token`      CHAR(64)        NOT NULL,
    `expires_at` DATETIME        NOT NULL,
    `created_at` DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_ev_token` (`token`),
    INDEX `idx_ev_user_id`   (`user_id`),
    CONSTRAINT `fk_ev_user_id`
        FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- PASSWORD RESETS
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `password_resets` (
    `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id`    BIGINT UNSIGNED NOT NULL,
    `token`      CHAR(64)        NOT NULL,
    `expires_at` DATETIME        NOT NULL,
    `used_at`    DATETIME        NULL,
    `created_at` DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_pr_token` (`token`),
    INDEX `idx_pr_user_id`   (`user_id`),
    CONSTRAINT `fk_pr_user_id`
        FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- RATE LIMITS
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `rate_limits` (
    `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `bucket_key` CHAR(32)        NOT NULL COMMENT 'MD5(ip + endpoint_group)',
    `ip_address` VARCHAR(45)     NOT NULL,
    `route`      VARCHAR(255)    NOT NULL,
    `created_at` DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_rl_bucket_key` (`bucket_key`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
