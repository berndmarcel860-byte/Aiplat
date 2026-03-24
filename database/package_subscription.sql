-- Migration: Add subscription_enabled to system_settings
-- This allows admins to toggle whether the package subscription feature is active.

ALTER TABLE `system_settings`
    ADD COLUMN `subscription_enabled` tinyint(1) NOT NULL DEFAULT 1
        COMMENT 'Enable/disable the package subscription feature for users (1=enabled, 0=disabled)'
    AFTER `dashboard_theme`;
