-- Migration: contact_leads table
-- Stores leads submitted via the Schnellbewertung (Quick Loss Estimator) form on the homepage.
-- Safe to run multiple times because of IF NOT EXISTS / INSERT IGNORE.

CREATE TABLE IF NOT EXISTS `contact_leads` (
    `id`          INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `name`        VARCHAR(255)    NOT NULL,
    `email`       VARCHAR(255)    NOT NULL,
    `phone`       VARCHAR(50)     DEFAULT NULL,
    `loss_amount` VARCHAR(50)     DEFAULT NULL,  -- value from the estimator dropdown
    `loss_type`   VARCHAR(50)     DEFAULT NULL,  -- value from the estimator dropdown
    `message`     TEXT            DEFAULT NULL,
    `ip_address`  VARCHAR(45)     DEFAULT NULL,
    `created_at`  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_email`      (`email`),
    KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
