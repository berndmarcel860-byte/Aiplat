-- Migration: Add package_subscription_enabled to system_settings
-- Run this once on your database to enable the package subscription toggle feature.

ALTER TABLE `system_settings`
    ADD COLUMN IF NOT EXISTS `package_subscription_enabled` tinyint(1) NOT NULL DEFAULT 1
    COMMENT 'Whether package subscription feature is enabled for users (1=enabled, 0=disabled)';

-- Default to enabled
UPDATE `system_settings` SET `package_subscription_enabled` = 1 WHERE id = 1;
