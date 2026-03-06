-- Migration: add template_key and user_id columns to email_logs
-- Run this once against your database before using EmailTemplateHelper.
--
-- EmailTemplateHelper (app/admin/email_template_helper.php) logs bulk
-- notification emails with a template_key (the string key from email_templates)
-- and an optional user_id so that sent emails can be correlated back to the
-- originating template and the recipient user.
--
-- These statements are idempotent — safe to run even if the columns already exist.

ALTER TABLE `email_logs`
    ADD COLUMN IF NOT EXISTS `template_key` VARCHAR(255) DEFAULT NULL AFTER `subject`,
    ADD COLUMN IF NOT EXISTS `user_id`      INT UNSIGNED DEFAULT NULL AFTER `tracking_token`,
    ADD INDEX  IF NOT EXISTS `idx_el_template_key` (`template_key`),
    ADD INDEX  IF NOT EXISTS `idx_el_user_id`      (`user_id`);
