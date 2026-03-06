-- Migration: email_tracking table
-- Run this once against your database before using email open tracking.
-- This table records each time an email is opened (one row per open event).
-- The email_logs table must have columns tracking_token VARCHAR(64) and opened_at DATETIME.

CREATE TABLE IF NOT EXISTS `email_tracking` (
    `id`             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `tracking_token` VARCHAR(64)     NOT NULL,
    `ip_address`     VARCHAR(45)     NOT NULL DEFAULT '',
    `user_agent`     TEXT,
    `referrer`       VARCHAR(2048)   DEFAULT NULL,
    `opened_at`      DATETIME        NOT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_tracking_token` (`tracking_token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Ensure email_logs has the tracking_token and opened_at columns.
-- These are safe to run even if the columns already exist (IF NOT EXISTS guard).
ALTER TABLE `email_logs`
    ADD COLUMN IF NOT EXISTS `tracking_token` VARCHAR(64) DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS `opened_at`      DATETIME    DEFAULT NULL,
    ADD INDEX IF NOT EXISTS `idx_el_tracking_token` (`tracking_token`);
