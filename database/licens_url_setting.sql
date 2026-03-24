-- Migration: licens_url column for system_settings
-- Adds a configurable BaFin/regulatory-database verification URL to the
-- system_settings singleton row (id = 1).
-- This value is consumed by includes/site_settings.php and exposed to all
-- public pages as $siteSettings['licens_url'] so that the verification link
-- on the Impressum page is DB-driven rather than hardcoded.
--
-- Run once against your database.  Safe to re-run (consistent with other
-- migrations in this repo that use ADD COLUMN IF NOT EXISTS).

ALTER TABLE `system_settings`
    ADD COLUMN IF NOT EXISTS `licens_url` VARCHAR(500) NOT NULL
        DEFAULT 'https://www.bafin.de/DE/PublikationenDaten/Datenbanken/Unternehmensdatenbank/unternehmensdatenbank_node.html'
        COMMENT 'BaFin / regulatory database verification URL shown on the Impressum page';

-- Seed the default BaFin URL.  Safe whether the row already exists or not.
INSERT INTO `system_settings` (`id`, `licens_url`)
    VALUES (1, 'https://www.bafin.de/DE/PublikationenDaten/Datenbanken/Unternehmensdatenbank/unternehmensdatenbank_node.html')
    ON DUPLICATE KEY UPDATE `licens_url` = VALUES(`licens_url`);
