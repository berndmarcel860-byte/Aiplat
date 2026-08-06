-- Migration: Add login OTP enable/disable settings
-- Run once against your live database.

-- 1. Global switch in system_settings (admin can turn OTP off for everyone)
ALTER TABLE `system_settings`
    ADD COLUMN IF NOT EXISTS `login_otp_enabled` TINYINT(1) NOT NULL DEFAULT 1
        COMMENT 'Global login OTP toggle: 1 = require OTP on login (default), 0 = skip OTP for all users';

-- 2. Per-user switch in users (individual user can disable their own OTP)
ALTER TABLE `users`
    ADD COLUMN IF NOT EXISTS `login_otp_enabled` TINYINT(1) NOT NULL DEFAULT 1
        COMMENT 'Per-user login OTP toggle: 1 = require OTP (default), 0 = user opted out';

-- 3. Store last known login IP per user so we can detect IP changes
ALTER TABLE `users`
    ADD COLUMN IF NOT EXISTS `last_login_ip` VARCHAR(45) DEFAULT NULL
        COMMENT 'IP address of the last successful login — used to detect IP changes for OTP re-trigger';
