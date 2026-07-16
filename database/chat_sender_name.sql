-- ═══════════════════════════════════════════════════════════════════════════
-- Live Chat — add sender_name column to live_chat_messages
-- Stores the display name of the agent/admin who sent the message.
-- Run once after deploying the updated chat files.
-- ═══════════════════════════════════════════════════════════════════════════

ALTER TABLE `live_chat_messages`
    ADD COLUMN IF NOT EXISTS `sender_name` VARCHAR(100) DEFAULT NULL
        COMMENT 'Display name of admin/agent (NULL for user and bot rows)'
    AFTER `sender_type`;
