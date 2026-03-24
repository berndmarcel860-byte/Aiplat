-- Migration: logo_url column for system_settings
-- Adds a configurable logo URL to the system_settings singleton row (id = 1).
-- The value is updated automatically when an admin uploads a logo via the
-- admin settings page; the URL is constructed from the actual request host so
-- it works on any domain without hardcoding.
--
-- Run once against your database.  Safe to re-run.

ALTER TABLE `system_settings`
    ADD COLUMN IF NOT EXISTS `logo_url` VARCHAR(512) NOT NULL DEFAULT ''
    COMMENT 'Absolute URL to the site logo (e.g. https://example.com/assets/img/logo.png)';
