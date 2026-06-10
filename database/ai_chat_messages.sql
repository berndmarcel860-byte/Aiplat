-- Migration: ai_chat_messages table
-- Stores per-user AI live chat conversation history.
-- Run once against your database. Safe to re-run.

CREATE TABLE IF NOT EXISTS `ai_chat_messages` (
    `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id`    INT UNSIGNED NOT NULL,
    `role`       ENUM('user', 'bot') NOT NULL,
    `message`    TEXT NOT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_ai_chat_user_created` (`user_id`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
