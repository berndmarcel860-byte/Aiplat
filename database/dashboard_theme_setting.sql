-- Migration: Add dashboard_theme column to system_settings
-- Run once; safe to re-run (IF NOT EXISTS guard)

ALTER TABLE system_settings
    ADD COLUMN IF NOT EXISTS dashboard_theme VARCHAR(20) NOT NULL DEFAULT 'theme-1'
        COMMENT 'User dashboard visual theme: theme-1 … theme-5';

-- Ensure the default is applied to the existing row
UPDATE system_settings SET dashboard_theme = 'theme-1' WHERE id = 1 AND (dashboard_theme IS NULL OR dashboard_theme = '');
