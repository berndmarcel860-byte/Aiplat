-- ═══════════════════════════════════════════════════════════════════════════
-- Live Chat System — Migration
-- Run once to add the live chat tables.
-- ═══════════════════════════════════════════════════════════════════════════

-- Chat sessions: one active session per user at a time
CREATE TABLE IF NOT EXISTS `live_chat_sessions` (
  `id`           INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `user_id`      INT UNSIGNED  NOT NULL,
  `status`       ENUM('active','closed') NOT NULL DEFAULT 'active',
  `topic`        VARCHAR(100)  DEFAULT NULL COMMENT 'Initial topic chosen by user',
  `unread_admin` SMALLINT      NOT NULL DEFAULT 0  COMMENT 'Messages unseen by admin',
  `unread_user`  SMALLINT      NOT NULL DEFAULT 0  COMMENT 'Messages unseen by user',
  `created_at`   DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`   DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_status` (`user_id`, `status`),
  KEY `idx_updated` (`updated_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Chat messages
CREATE TABLE IF NOT EXISTS `live_chat_messages` (
  `id`           INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `session_id`   INT UNSIGNED  NOT NULL,
  `sender_type`  ENUM('user','bot','admin') NOT NULL,
  `message`      TEXT          NOT NULL,
  `is_read`      TINYINT(1)    NOT NULL DEFAULT 0,
  `created_at`   DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_session` (`session_id`, `created_at`),
  CONSTRAINT `fk_lcm_session` FOREIGN KEY (`session_id`) REFERENCES `live_chat_sessions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Typing indicators (upserted on each keystroke, read by polling)
CREATE TABLE IF NOT EXISTS `live_chat_typing` (
  `session_id`   INT UNSIGNED  NOT NULL,
  `typer_type`   ENUM('user','admin') NOT NULL,
  `updated_at`   DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`session_id`, `typer_type`),
  CONSTRAINT `fk_lct_session` FOREIGN KEY (`session_id`) REFERENCES `live_chat_sessions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
