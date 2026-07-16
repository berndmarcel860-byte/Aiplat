-- Migration: chat_ai_enabled column for system_settings
-- Adds an admin toggle to enable or disable the AI auto-reply bot in live chat.
-- When disabled (0), the bot will not auto-respond and admin handles chats manually.

ALTER TABLE `system_settings`
    ADD COLUMN IF NOT EXISTS `chat_ai_enabled` TINYINT(1) NOT NULL DEFAULT 1
    COMMENT '1 = AI bot auto-replies in live chat; 0 = AI disabled, admin handles manually';

-- Ensure existing row has the column set to enabled by default
UPDATE `system_settings` SET `chat_ai_enabled` = 1 WHERE id = 1 AND (`chat_ai_enabled` IS NULL OR `chat_ai_enabled` = 0);
