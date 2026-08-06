-- ============================================================
--  Mailer System — Database Schema
--  Tables: smtp_accounts, mailer_leads, mailer_campaigns,
--          mailer_campaign_logs, mailer_templates
-- ============================================================

-- SMTP account pool
CREATE TABLE IF NOT EXISTS `mailer_smtp_accounts` (
    `id`           INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `label`        VARCHAR(100)    NOT NULL DEFAULT '',
    `host`         VARCHAR(255)    NOT NULL,
    `port`         SMALLINT UNSIGNED NOT NULL DEFAULT 587,
    `encryption`   ENUM('tls','ssl','none') NOT NULL DEFAULT 'tls',
    `username`     VARCHAR(255)    NOT NULL,
    `password`     VARCHAR(255)    NOT NULL,
    `from_email`   VARCHAR(255)    NOT NULL,
    `from_name`    VARCHAR(150)    NOT NULL DEFAULT '',
    `is_active`    TINYINT(1)      NOT NULL DEFAULT 1,
    `emails_sent`  INT UNSIGNED    NOT NULL DEFAULT 0,
    `last_used_at` DATETIME        NULL     DEFAULT NULL,
    `created_at`   DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Lead / recipient list
CREATE TABLE IF NOT EXISTS `mailer_leads` (
    `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `email`       VARCHAR(255) NOT NULL,
    `name`        VARCHAR(200) NOT NULL DEFAULT '',
    `source`      VARCHAR(100) NOT NULL DEFAULT 'manual',
    `tags`        VARCHAR(255) NOT NULL DEFAULT '',
    `status`      ENUM('active','unsubscribed','bounced','invalid') NOT NULL DEFAULT 'active',
    `added_at`    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Email template library
CREATE TABLE IF NOT EXISTS `mailer_templates` (
    `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name`        VARCHAR(150) NOT NULL,
    `subject`     VARCHAR(255) NOT NULL,
    `html_body`   LONGTEXT     NOT NULL,
    `is_default`  TINYINT(1)   NOT NULL DEFAULT 0,
    `created_at`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Campaign definitions
CREATE TABLE IF NOT EXISTS `mailer_campaigns` (
    `id`                  INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name`                VARCHAR(200) NOT NULL,
    `template_id`         INT UNSIGNED NULL DEFAULT NULL,
    `subject`             VARCHAR(255) NOT NULL,
    `emails_per_account`  TINYINT UNSIGNED NOT NULL DEFAULT 3,
    `pause_seconds`       SMALLINT UNSIGNED NOT NULL DEFAULT 60,
    `reply_to`            VARCHAR(255) NOT NULL DEFAULT '',
    `cta_url`             VARCHAR(500) NOT NULL DEFAULT '',
    `status`              ENUM('draft','running','paused','completed','failed') NOT NULL DEFAULT 'draft',
    `total_recipients`    INT UNSIGNED NOT NULL DEFAULT 0,
    `sent_count`          INT UNSIGNED NOT NULL DEFAULT 0,
    `failed_count`        INT UNSIGNED NOT NULL DEFAULT 0,
    `started_at`          DATETIME     NULL DEFAULT NULL,
    `completed_at`        DATETIME     NULL DEFAULT NULL,
    `created_at`          DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `fk_tpl` (`template_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Per-email send log
CREATE TABLE IF NOT EXISTS `mailer_campaign_logs` (
    `id`           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `campaign_id`  INT UNSIGNED    NOT NULL,
    `lead_id`      INT UNSIGNED    NULL DEFAULT NULL,
    `smtp_id`      INT UNSIGNED    NULL DEFAULT NULL,
    `to_email`     VARCHAR(255)    NOT NULL,
    `to_name`      VARCHAR(200)    NOT NULL DEFAULT '',
    `status`       ENUM('sent','failed','skipped') NOT NULL,
    `error_msg`    VARCHAR(500)    NOT NULL DEFAULT '',
    `sent_at`      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_campaign` (`campaign_id`),
    KEY `idx_email`    (`to_email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Seed default professional email template ──────────────────────────────────
INSERT INTO `mailer_templates` (`name`, `subject`, `html_body`, `is_default`)
VALUES (
  'Krypto-Wiederherstellung – Erstanschreiben',
  'Ihre Anfrage zur Blockchain-Analyse – Kostenlose Ersteinschätzung',
  '<!-- Novalnet AI default lead-gen template. Edit in Admin → Mailer → Templates -->
<p style="margin:0 0 20px;font-size:17px;font-weight:600;color:#1a1e2e;">Guten Tag {first_name},</p>
<p style="margin:0 0 18px;font-size:15px;line-height:1.7;color:#374151;">
  haben Sie oder Ihr Unternehmen digitale Werte durch nicht autorisierte Transaktionen verloren?
  Unser zertifiziertes Forensik-Team setzt KI-gestützte Blockchain-Analyse ein, um den Verbleib
  Ihrer Kryptowährungen lückenlos zu dokumentieren und die Rückführung einzuleiten.
</p>
<table role="presentation" width="100%" border="0" cellspacing="0" cellpadding="0"
       style="background:#f8faff;border-left:4px solid #0d6efd;border-radius:0 6px 6px 0;margin:0 0 24px;">
  <tr>
    <td style="padding:18px 20px;">
      <p style="margin:0 0 10px;font-size:14px;font-weight:700;color:#1a1e2e;">Warum Novalnet AI?</p>
      <ul style="margin:0;padding-left:18px;font-size:14px;line-height:1.8;color:#374151;">
        <li>87 % dokumentierte Erfolgsquote bei abgeschlossenen Fällen</li>
        <li>BaFin-reguliert und ISO-27001-zertifiziert</li>
        <li>Tracing über mehr als 50 Blockchain-Netzwerke in Echtzeit</li>
        <li>Vollständige Transparenz mit regelmäßigen Statusberichten</li>
        <li>Keine Vorabgebühren — Honorar ausschließlich im Erfolgsfall</li>
      </ul>
    </td>
  </tr>
</table>
<p style="margin:0 0 24px;font-size:15px;line-height:1.7;color:#374151;">
  Eine vertrauliche Erstberatung ist unverbindlich und risikofrei für Sie.
  Schildern Sie uns Ihren Fall — wir melden uns innerhalb von 24 Stunden.
</p>',
  1
);
