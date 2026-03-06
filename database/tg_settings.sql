-- Migration: tg_settings
-- Creates the table used by TelegramHelper.php and the admin Telegram settings panel.
-- Run this once against your database before using Telegram ticket notifications.
--
-- This is a singleton settings table: the application always reads and writes the
-- row with id = 1. The id column is intentionally NOT AUTO_INCREMENT because the
-- PHP code always supplies id = 1 explicitly in every INSERT ... ON DUPLICATE KEY
-- UPDATE statement.

CREATE TABLE IF NOT EXISTS `tg_settings` (
    `id`         INT UNSIGNED NOT NULL,
    `bot_token`  VARCHAR(255) NOT NULL DEFAULT '',
    `chat_id`    VARCHAR(100) NOT NULL DEFAULT '',
    `is_enabled` TINYINT(1)   NOT NULL DEFAULT 0,
    `created_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
