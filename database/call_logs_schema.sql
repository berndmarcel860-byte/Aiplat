-- ============================================================
--  Voice Call Logs — Database Schema
--  Table: voice_call_logs
--
--  Run this once in your MySQL/MariaDB console:
--      mysql -u <user> -p <database> < database/call_logs_schema.sql
-- ============================================================

CREATE TABLE IF NOT EXISTS `voice_call_logs` (
    `id`            INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    -- which chat session the call belongs to
    `session_id`    INT UNSIGNED    NOT NULL,
    -- who initiated the call: 'user' or 'admin'
    `initiated_by`  ENUM('user','admin') NOT NULL DEFAULT 'user',
    -- user involved (may be NULL for guest/anonymous sessions)
    `user_id`       INT UNSIGNED    NULL DEFAULT NULL,
    -- call outcome
    `status`        ENUM('ringing','answered','rejected','missed','ended') NOT NULL DEFAULT 'ringing',
    -- timestamps
    `started_at`    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `answered_at`   DATETIME        NULL DEFAULT NULL,
    `ended_at`      DATETIME        NULL DEFAULT NULL,
    -- duration in seconds (NULL until the call ends)
    `duration_sec`  INT UNSIGNED    NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_vcl_session`  (`session_id`),
    KEY `idx_vcl_user`     (`user_id`),
    KEY `idx_vcl_status`   (`status`),
    KEY `idx_vcl_started`  (`started_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
