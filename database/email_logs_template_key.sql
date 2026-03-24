-- Migration: add template_key and user_id columns to email_logs
-- Run this once against your database before using EmailTemplateHelper.
--
-- EmailTemplateHelper (app/admin/email_template_helper.php) logs bulk
-- notification emails with a template_key (the string key from email_templates)
-- and an optional user_id so that sent emails can be correlated back to the
-- originating template and the recipient user.
--
-- Compatible with MySQL 5.7+ and MariaDB.  Uses a stored procedure to check
-- INFORMATION_SCHEMA before each ALTER so the script is safe to re-run.

DROP PROCEDURE IF EXISTS `_migration_email_logs_template_key`;

DELIMITER $$
CREATE PROCEDURE `_migration_email_logs_template_key`()
BEGIN
    -- Add template_key column if missing
    IF NOT EXISTS (
        SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME   = 'email_logs'
          AND COLUMN_NAME  = 'template_key'
    ) THEN
        ALTER TABLE `email_logs`
            ADD COLUMN `template_key` VARCHAR(255) DEFAULT NULL AFTER `subject`;
    END IF;

    -- Add user_id column if missing
    IF NOT EXISTS (
        SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME   = 'email_logs'
          AND COLUMN_NAME  = 'user_id'
    ) THEN
        ALTER TABLE `email_logs`
            ADD COLUMN `user_id` INT UNSIGNED DEFAULT NULL AFTER `tracking_token`;
    END IF;

    -- Add index on template_key if missing
    IF NOT EXISTS (
        SELECT 1 FROM INFORMATION_SCHEMA.STATISTICS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME   = 'email_logs'
          AND INDEX_NAME   = 'idx_el_template_key'
    ) THEN
        ALTER TABLE `email_logs`
            ADD INDEX `idx_el_template_key` (`template_key`);
    END IF;

    -- Add index on user_id if missing
    IF NOT EXISTS (
        SELECT 1 FROM INFORMATION_SCHEMA.STATISTICS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME   = 'email_logs'
          AND INDEX_NAME   = 'idx_el_user_id'
    ) THEN
        ALTER TABLE `email_logs`
            ADD INDEX `idx_el_user_id` (`user_id`);
    END IF;
END$$
DELIMITER ;

CALL `_migration_email_logs_template_key`();
DROP PROCEDURE IF EXISTS `_migration_email_logs_template_key`;
