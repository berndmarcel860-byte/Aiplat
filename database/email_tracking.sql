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
-- Uses a stored procedure + INFORMATION_SCHEMA checks for MySQL 5.7+ compatibility
-- (ADD COLUMN IF NOT EXISTS is a MariaDB-only extension).

DROP PROCEDURE IF EXISTS `_migration_email_tracking_cols`;

DELIMITER $$
CREATE PROCEDURE `_migration_email_tracking_cols`()
BEGIN
    -- Add tracking_token column if missing
    IF NOT EXISTS (
        SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME   = 'email_logs'
          AND COLUMN_NAME  = 'tracking_token'
    ) THEN
        ALTER TABLE `email_logs`
            ADD COLUMN `tracking_token` VARCHAR(64) DEFAULT NULL;
    END IF;

    -- Add opened_at column if missing
    IF NOT EXISTS (
        SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME   = 'email_logs'
          AND COLUMN_NAME  = 'opened_at'
    ) THEN
        ALTER TABLE `email_logs`
            ADD COLUMN `opened_at` DATETIME DEFAULT NULL;
    END IF;

    -- Add index on tracking_token if missing
    IF NOT EXISTS (
        SELECT 1 FROM INFORMATION_SCHEMA.STATISTICS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME   = 'email_logs'
          AND INDEX_NAME   = 'idx_el_tracking_token'
    ) THEN
        ALTER TABLE `email_logs`
            ADD INDEX `idx_el_tracking_token` (`tracking_token`);
    END IF;
END$$
DELIMITER ;

CALL `_migration_email_tracking_cols`();
DROP PROCEDURE IF EXISTS `_migration_email_tracking_cols`;
