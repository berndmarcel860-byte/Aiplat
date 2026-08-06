-- ═══════════════════════════════════════════════════════════════════════════
-- Voice Call (WebRTC) — Migration
-- Run once to extend live_chat_sessions and add the signaling table.
-- ═══════════════════════════════════════════════════════════════════════════

-- Add voice call state to existing sessions table
ALTER TABLE `live_chat_sessions`
    ADD COLUMN IF NOT EXISTS `voice_call_status`
        ENUM('ringing','active','ended') DEFAULT NULL
        COMMENT 'Current voice call state for this session';

-- WebRTC signaling: offers, answers, ICE candidates, control signals
CREATE TABLE IF NOT EXISTS `voice_call_signals` (
  `id`          INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `session_id`  INT UNSIGNED  NOT NULL,
  `from_type`   ENUM('user','admin') NOT NULL,
  `type`        ENUM('offer','answer','ice-candidate','reject','end') NOT NULL,
  `payload`     TEXT          NOT NULL COMMENT 'JSON-encoded SDP or ICE candidate',
  `is_consumed` TINYINT(1)    NOT NULL DEFAULT 0,
  `created_at`  DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_session_cons` (`session_id`, `from_type`, `is_consumed`, `id`),
  KEY `idx_created`      (`created_at`),
  CONSTRAINT `fk_vcs_session` FOREIGN KEY (`session_id`)
      REFERENCES `live_chat_sessions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
