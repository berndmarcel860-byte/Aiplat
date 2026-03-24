-- ============================================================
--  Mailer External Configuration — Migration
--
--  The mailer subsystem now supports a dedicated external
--  database and a separate base URL, both driven by environment
--  variables.  No schema changes are required for the external
--  DB path; this file documents the configuration approach and
--  adds a `mailer_settings` table for optional admin-UI storage
--  of non-sensitive settings (e.g. brand overrides for emails).
--
--  Environment variables (set in your server / .env file):
--  ──────────────────────────────────────────────────────────
--  MAILER_DB_HOST      External mailer DB host
--                        (default: DB_HOST or 'localhost')
--  MAILER_DB_PORT      External mailer DB port
--                        (default: DB_PORT or 3306)
--  MAILER_DB_NAME      External mailer DB name
--                        (default: DB_NAME or 'novalnet-ai')
--  MAILER_DB_USER      External mailer DB user
--                        (default: DB_USER or 'novalnet')
--  MAILER_DB_PASSWORD  External mailer DB password
--                        (default: DB_PASSWORD or '')
--  MAILER_BASE_URL     Base URL used in email links
--                        (default: APP_URL or 'https://your-domain.com')
--  MAILER_TIMEZONE     Timezone for mailer DB session
--                        (default: APP_TIMEZONE or 'Europe/Berlin')
--
--  When the MAILER_DB_* variables point to an external host,
--  the mailer schema (tables below) must exist on that host.
--  Run database/mailer_schema.sql on the external database first.
-- ============================================================

-- Optional: store non-sensitive mailer settings that override defaults.
-- Sensitive credentials must always use environment variables, never the DB.
CREATE TABLE IF NOT EXISTS `mailer_settings` (
    `setting_key`   VARCHAR(100) NOT NULL,
    `setting_value` TEXT         NOT NULL DEFAULT '',
    `updated_at`    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
  COMMENT='Non-sensitive mailer configuration overrides. Credentials must use env vars.';

-- Seed default values (safe to re-run; INSERT IGNORE skips existing rows)
INSERT IGNORE INTO `mailer_settings` (`setting_key`, `setting_value`) VALUES
    ('brand_name',      'Novalnet AI'),
    ('company_address', 'Novalnet AI GmbH · BaFin-reg. · Deutschland');
