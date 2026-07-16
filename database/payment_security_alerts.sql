-- Migration: payment_security_alerts
-- Logs every time an admin message containing an unofficial payment address
-- was intercepted and filtered before being delivered to a user.
-- Run once: mysql krypto-x < database/payment_security_alerts.sql

CREATE TABLE IF NOT EXISTS `payment_security_alerts` (
  `id`              INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `channel`         ENUM('ticket_reply','live_chat','direct_email','bulk_email') NOT NULL,
  `admin_id`        INT DEFAULT NULL COMMENT 'admin_id from admins table',
  `user_id`         INT DEFAULT NULL COMMENT 'affected user (NULL for bulk)',
  `entity_id`       INT DEFAULT NULL COMMENT 'ticket_id / chat session_id / NULL for email',
  `original_message` TEXT NOT NULL,
  `filtered_message` TEXT NOT NULL,
  `addresses_found` TEXT NOT NULL COMMENT 'JSON array of unauthorized addresses that were detected',
  `is_reviewed`     TINYINT(1) NOT NULL DEFAULT 0,
  `reviewed_by`     INT DEFAULT NULL,
  `reviewed_at`     DATETIME DEFAULT NULL,
  `review_note`     TEXT DEFAULT NULL,
  `created_at`      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_channel`     (`channel`),
  KEY `idx_admin_id`    (`admin_id`),
  KEY `idx_user_id`     (`user_id`),
  KEY `idx_is_reviewed` (`is_reviewed`),
  KEY `idx_created_at`  (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
