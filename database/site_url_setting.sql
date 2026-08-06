-- Migration: site_url column for system_settings
-- Adds a configurable base URL to the system_settings singleton row (id = 1).
-- This value is consumed by includes/site_settings.php and exposed to all
-- public pages as $siteSettings['site_url'] so that canonical page URLs are
-- DB-driven rather than hardcoded.
--
-- Run once against your database.  Safe to re-run (consistent with other
-- migrations in this repo that use ADD COLUMN IF NOT EXISTS).

ALTER TABLE `system_settings`
    ADD COLUMN IF NOT EXISTS `site_url` VARCHAR(255) NOT NULL DEFAULT 'https://novalnet-ai.de'
    COMMENT 'Base URL of the site (no trailing slash)';

-- Seed the current production value.  Uses INSERT...ON DUPLICATE KEY UPDATE
-- so the statement is safe whether the row already exists or not.
INSERT INTO `system_settings` (`id`, `site_url`)
    VALUES (1, 'https://novalnet-ai.de')
    ON DUPLICATE KEY UPDATE `site_url` = VALUES(`site_url`);
