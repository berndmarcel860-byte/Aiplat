-- Migration: add one-time auto reply marker for support tickets
-- Safe to re-run: column is only added when missing.

SET @column_exists := (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'support_tickets'
      AND COLUMN_NAME = 'auto_reply_sent'
);

SET @sql := IF(
    @column_exists = 0,
    'ALTER TABLE support_tickets ADD COLUMN auto_reply_sent TINYINT(1) NOT NULL DEFAULT 0 AFTER assigned_admin_id',
    'SELECT "support_tickets.auto_reply_sent already exists"'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

