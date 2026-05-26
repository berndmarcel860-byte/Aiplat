-- Migration: Trial case setup cron settings in system_settings
-- Enables admin-configurable values for cron_trial_case_setup.php.

ALTER TABLE `system_settings`
    ADD COLUMN IF NOT EXISTS `trial_active_window_hours` INT NOT NULL DEFAULT 48
        COMMENT 'Active trial package window used for candidate selection',
    ADD COLUMN IF NOT EXISTS `trial_case_interval_minutes` INT NOT NULL DEFAULT 5
        COMMENT 'Minimum minutes between auto-created trial cases per user',
    ADD COLUMN IF NOT EXISTS `trial_initial_delay_minutes` INT NOT NULL DEFAULT 5
        COMMENT 'Minutes to wait after trial activation before first case creation',
    ADD COLUMN IF NOT EXISTS `trial_max_cases_per_run` INT NOT NULL DEFAULT 2
        COMMENT 'Maximum number of cases this cron may create in one execution',
    ADD COLUMN IF NOT EXISTS `trial_cases_per_user` INT NOT NULL DEFAULT 3
        COMMENT 'Number of distinct platforms to rotate across per user',
    ADD COLUMN IF NOT EXISTS `trial_total_amount` DECIMAL(15,2) NOT NULL DEFAULT 150000.00
        COMMENT 'Total distributed reported amount target per trial user',
    ADD COLUMN IF NOT EXISTS `trial_amount_variation_percent` DECIMAL(5,2) NOT NULL DEFAULT 20.00
        COMMENT 'Allowed +/- variation percentage for each auto-created trial case amount';
