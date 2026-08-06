-- Migration: openai_api_key column for system_settings
-- Adds an optional OpenAI API key to the system_settings singleton row (id = 1).
-- Used by the Email Notificasion TMP page to generate AI-assisted email content.
--
-- Run once against your database.  Safe to re-run.

ALTER TABLE `system_settings`
    ADD COLUMN IF NOT EXISTS `openai_api_key` VARCHAR(512) NOT NULL DEFAULT ''
    COMMENT 'OpenAI API key for AI-assisted email content generation (optional)';
