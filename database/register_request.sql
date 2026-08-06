-- Migration: register_request table
-- Stores contact/registration requests submitted via the modal form on the register page.
-- Safe to run multiple times because of IF NOT EXISTS.

CREATE TABLE IF NOT EXISTS `register_request` (
    `id`          INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `first_name`  VARCHAR(100)    NOT NULL,
    `last_name`   VARCHAR(100)    NOT NULL,
    `email`       VARCHAR(255)    NOT NULL,
    `phone`       VARCHAR(50)     NOT NULL,
    `amount`      VARCHAR(50)     NOT NULL,  -- e.g. "5000-20000", "500000+"
    `year`        SMALLINT UNSIGNED NOT NULL, -- year of loss (2000-2026)
    `platforms`   TEXT            DEFAULT NULL, -- comma-separated list of platforms
    `details`     TEXT            NOT NULL,
    `ip_address`  VARCHAR(45)     DEFAULT NULL,
    `status`      ENUM('new','contacted','closed') NOT NULL DEFAULT 'new',
    `created_at`  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_email`      (`email`),
    KEY `idx_status`     (`status`),
    KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
