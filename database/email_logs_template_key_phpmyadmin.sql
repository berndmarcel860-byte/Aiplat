-- ============================================================
-- Migration: add template_key and user_id to email_logs
-- FOR USE IN phpMyAdmin SQL tab (or any MySQL client that does
-- NOT support the DELIMITER command).
--
-- Paste this entire script into the phpMyAdmin SQL tab and
-- click "Go".  It is idempotent — safe to run multiple times.
--
-- Technique: SET / PREPARE / EXECUTE avoids stored procedures
-- and the DELIMITER directive, so it works everywhere.
-- ============================================================

-- --------------------------------------------------------
-- 1. Add column: template_key
-- --------------------------------------------------------
SET @col_exists := (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME   = 'email_logs'
      AND COLUMN_NAME  = 'template_key'
);
SET @sql := IF(
    @col_exists = 0,
    'ALTER TABLE `email_logs` ADD COLUMN `template_key` VARCHAR(255) DEFAULT NULL AFTER `subject`',
    'SELECT ''template_key column already exists — skipped'''
);
PREPARE _stmt FROM @sql;
EXECUTE _stmt;
DEALLOCATE PREPARE _stmt;

-- --------------------------------------------------------
-- 2. Add column: user_id
-- --------------------------------------------------------
SET @col_exists := (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME   = 'email_logs'
      AND COLUMN_NAME  = 'user_id'
);
SET @sql := IF(
    @col_exists = 0,
    'ALTER TABLE `email_logs` ADD COLUMN `user_id` INT UNSIGNED DEFAULT NULL AFTER `tracking_token`',
    'SELECT ''user_id column already exists — skipped'''
);
PREPARE _stmt FROM @sql;
EXECUTE _stmt;
DEALLOCATE PREPARE _stmt;

-- --------------------------------------------------------
-- 3. Add index: idx_el_template_key
-- --------------------------------------------------------
SET @idx_exists := (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME   = 'email_logs'
      AND INDEX_NAME   = 'idx_el_template_key'
);
SET @sql := IF(
    @idx_exists = 0,
    'ALTER TABLE `email_logs` ADD INDEX `idx_el_template_key` (`template_key`)',
    'SELECT ''idx_el_template_key index already exists — skipped'''
);
PREPARE _stmt FROM @sql;
EXECUTE _stmt;
DEALLOCATE PREPARE _stmt;

-- --------------------------------------------------------
-- 4. Add index: idx_el_user_id
-- --------------------------------------------------------
SET @idx_exists := (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME   = 'email_logs'
      AND INDEX_NAME   = 'idx_el_user_id'
);
SET @sql := IF(
    @idx_exists = 0,
    'ALTER TABLE `email_logs` ADD INDEX `idx_el_user_id` (`user_id`)',
    'SELECT ''idx_el_user_id index already exists — skipped'''
);
PREPARE _stmt FROM @sql;
EXECUTE _stmt;
DEALLOCATE PREPARE _stmt;
