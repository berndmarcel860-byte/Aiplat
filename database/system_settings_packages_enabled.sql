ALTER TABLE `system_settings`
    ADD COLUMN IF NOT EXISTS `packages_enabled` TINYINT(1) NOT NULL DEFAULT 1
    COMMENT 'Global toggle for user package feature visibility';
