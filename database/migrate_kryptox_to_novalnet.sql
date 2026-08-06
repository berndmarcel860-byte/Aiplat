-- ============================================================
-- Migration: kryptox → novalnet-ai
-- Applies every schema change that is present in novalnet-ai
-- but missing from the older kryptox database.
--
-- Safe to run multiple times:
--   • CREATE TABLE     uses IF NOT EXISTS
--   • ADD COLUMN       uses a stored-procedure + INFORMATION_SCHEMA check
--   • ADD INDEX        uses a stored-procedure + INFORMATION_SCHEMA check
--
-- Run order matters because of the FK from crypto_networks → cryptocurrencies.
-- Execute this file once in your MySQL/MariaDB console:
--     mysql -u <user> -p <database> < migrate_kryptox_to_novalnet.sql
-- ============================================================

-- ============================================================
-- SECTION 1 – NEW TABLES
-- ============================================================

-- 1a) cryptocurrencies
CREATE TABLE IF NOT EXISTS `cryptocurrencies` (
  `id`          INT            NOT NULL AUTO_INCREMENT,
  `symbol`      VARCHAR(20)    NOT NULL,
  `name`        VARCHAR(100)   NOT NULL,
  `icon`        VARCHAR(255)   DEFAULT NULL,
  `description` TEXT           DEFAULT NULL,
  `is_active`   TINYINT(1)     NOT NULL DEFAULT '1',
  `sort_order`  INT            NOT NULL DEFAULT '0',
  `created_at`  TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`  TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_cryptocurrencies_symbol` (`symbol`),
  KEY `idx_cryptocurrencies_is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- 1b) crypto_networks (depends on cryptocurrencies)
CREATE TABLE IF NOT EXISTS `crypto_networks` (
  `id`           INT           NOT NULL AUTO_INCREMENT,
  `crypto_id`    INT           NOT NULL,
  `network_name` VARCHAR(100)  NOT NULL,
  `network_type` VARCHAR(50)   NOT NULL,
  `chain_id`     VARCHAR(50)   DEFAULT NULL,
  `explorer_url` VARCHAR(255)  DEFAULT NULL,
  `is_active`    TINYINT(1)    NOT NULL DEFAULT '1',
  `sort_order`   INT           NOT NULL DEFAULT '0',
  `created_at`   TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`   TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_crypto_networks_crypto_id` (`crypto_id`),
  KEY `idx_crypto_networks_is_active` (`is_active`),
  CONSTRAINT `fk_crypto_networks_crypto_id`
      FOREIGN KEY (`crypto_id`) REFERENCES `cryptocurrencies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- 1c) settings (general company/site settings, separate from system_settings)
CREATE TABLE IF NOT EXISTS `settings` (
  `id`              INT           NOT NULL AUTO_INCREMENT,
  `company_name`    VARCHAR(255)  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'CryptoFinanz',
  `company_address` VARCHAR(255)  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `company_city`    VARCHAR(100)  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `company_country` VARCHAR(100)  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'Deutschland',
  `support_email`   VARCHAR(255)  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `support_phone`   VARCHAR(50)   CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `website_url`     VARCHAR(255)  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `terms_url`       VARCHAR(255)  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `privacy_url`     VARCHAR(255)  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- SECTION 2 – NEW COLUMNS IN EXISTING TABLES
-- Uses a helper stored procedure so each ALTER is idempotent.
-- ============================================================

DROP PROCEDURE IF EXISTS `_mig_add_col`;

DELIMITER $$

CREATE PROCEDURE `_mig_add_col`(
    IN p_table  VARCHAR(64),
    IN p_col    VARCHAR(64),
    IN p_after  VARCHAR(64),   -- column to place the new one AFTER (NULL = append)
    IN p_def    TEXT           -- column definition, e.g. "VARCHAR(255) DEFAULT NULL"
)
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME   = p_table
          AND COLUMN_NAME  = p_col
    ) THEN
        SET @sql = CONCAT(
            'ALTER TABLE `', p_table, '` ADD COLUMN `', p_col, '` ', p_def,
            IF(p_after IS NOT NULL, CONCAT(' AFTER `', p_after, '`'), '')
        );
        PREPARE _stmt FROM @sql;
        EXECUTE _stmt;
        DEALLOCATE PREPARE _stmt;
    END IF;
END$$

DELIMITER ;

-- Helper to add an index idempotently
DROP PROCEDURE IF EXISTS `_mig_add_idx`;

DELIMITER $$

CREATE PROCEDURE `_mig_add_idx`(
    IN p_table  VARCHAR(64),
    IN p_idx    VARCHAR(64),
    IN p_cols   VARCHAR(255)   -- e.g. "col1, col2"
)
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM INFORMATION_SCHEMA.STATISTICS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME   = p_table
          AND INDEX_NAME   = p_idx
    ) THEN
        SET @sql = CONCAT(
            'ALTER TABLE `', p_table, '` ADD INDEX `', p_idx, '` (', p_cols, ')'
        );
        PREPARE _stmt FROM @sql;
        EXECUTE _stmt;
        DEALLOCATE PREPARE _stmt;
    END IF;
END$$

DELIMITER ;

-- ----------------------------------------------------------
-- 2a) email_templates → is_active
-- ----------------------------------------------------------
CALL `_mig_add_col`(
    'email_templates', 'is_active', 'updated_at',
    "ENUM('0','1') NOT NULL DEFAULT '1' COMMENT 'Whether this template is active'"
);

-- ----------------------------------------------------------
-- 2b) kyc_verification_requests → admin_notes
-- ----------------------------------------------------------
CALL `_mig_add_col`(
    'kyc_verification_requests', 'admin_notes', NULL,
    "TEXT COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Notes added by admin when creating/processing KYC'"
);

-- ----------------------------------------------------------
-- 2c) login_logs → reason
-- ----------------------------------------------------------
CALL `_mig_add_col`(
    'login_logs', 'reason', NULL,
    "VARCHAR(255) DEFAULT NULL COMMENT 'Reason for failed/blocked login attempt'"
);

-- ----------------------------------------------------------
-- 2d) system_settings → company_address, fca_reference_number
--     (site_url and licens_url already covered by existing migrations)
-- ----------------------------------------------------------
CALL `_mig_add_col`(
    'system_settings', 'company_address', NULL,
    "TEXT DEFAULT NULL COMMENT 'Full postal address of the company'"
);

CALL `_mig_add_col`(
    'system_settings', 'fca_reference_number', NULL,
    "VARCHAR(50) DEFAULT NULL COMMENT 'FCA (or equivalent) regulatory reference number'"
);

-- ----------------------------------------------------------
-- 2e) user_onboarding → where_lost
-- ----------------------------------------------------------
CALL `_mig_add_col`(
    'user_onboarding', 'where_lost', NULL,
    "VARCHAR(255) DEFAULT NULL COMMENT 'Platform or exchange where funds were lost (e.g., Binance, Coinbase)'"
);

-- ----------------------------------------------------------
-- 2f) users → email_verified_at, last_otp_verified_at, verification_token_expires
-- ----------------------------------------------------------
CALL `_mig_add_col`(
    'users', 'email_verified_at', NULL,
    "DATETIME DEFAULT NULL COMMENT 'Timestamp when user email was verified'"
);

CALL `_mig_add_col`(
    'users', 'last_otp_verified_at', NULL,
    "DATETIME DEFAULT NULL COMMENT 'Timestamp of last successful OTP verification'"
);

CALL `_mig_add_col`(
    'users', 'verification_token_expires', NULL,
    "DATETIME DEFAULT NULL COMMENT 'Expiration time for verification token (typically 1 hour from generation)'"
);

-- ----------------------------------------------------------
-- 2g) user_payment_methods – many new columns
--     The novalnet-ai schema restructured this table to support
--     both fiat and crypto payment methods in a single table,
--     along with a verification workflow.
-- ----------------------------------------------------------

-- Core new columns
CALL `_mig_add_col`(
    'user_payment_methods', 'type', NULL,
    "ENUM('fiat','crypto') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'fiat' COMMENT 'Payment method type'"
);

CALL `_mig_add_col`(
    'user_payment_methods', 'label', NULL,
    "VARCHAR(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'User-friendly label for the payment method'"
);

CALL `_mig_add_col`(
    'user_payment_methods', 'bank_name', NULL,
    "VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Name of bank'"
);

CALL `_mig_add_col`(
    'user_payment_methods', 'account_holder', NULL,
    "VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Name of account holder'"
);

CALL `_mig_add_col`(
    'user_payment_methods', 'iban', NULL,
    "VARCHAR(34) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'International Bank Account Number'"
);

CALL `_mig_add_col`(
    'user_payment_methods', 'bic', NULL,
    "VARCHAR(11) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Bank Identifier Code / SWIFT'"
);

CALL `_mig_add_col`(
    'user_payment_methods', 'account_number', NULL,
    "VARCHAR(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Bank account number (non-IBAN)'"
);

CALL `_mig_add_col`(
    'user_payment_methods', 'routing_number', NULL,
    "VARCHAR(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Routing number (US)'"
);

CALL `_mig_add_col`(
    'user_payment_methods', 'sort_code', NULL,
    "VARCHAR(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Sort code (UK)'"
);

-- Crypto-specific columns
CALL `_mig_add_col`(
    'user_payment_methods', 'cryptocurrency', NULL,
    "VARCHAR(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Type of cryptocurrency (BTC, ETH, USDT, etc.)'"
);

CALL `_mig_add_col`(
    'user_payment_methods', 'network', NULL,
    "VARCHAR(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Blockchain network (ERC-20, TRC-20, BEP-20, etc.)'"
);

CALL `_mig_add_col`(
    'user_payment_methods', 'wallet_address', NULL,
    "VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Cryptocurrency wallet address'"
);

-- Status / audit columns
CALL `_mig_add_col`(
    'user_payment_methods', 'status', NULL,
    "ENUM('active','pending','suspended') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'active' COMMENT 'Payment method status'"
);

CALL `_mig_add_col`(
    'user_payment_methods', 'is_verified', NULL,
    "TINYINT(1) DEFAULT '0' COMMENT 'Whether payment method has been verified'"
);

CALL `_mig_add_col`(
    'user_payment_methods', 'notes', NULL,
    "TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Additional notes or instructions'"
);

CALL `_mig_add_col`(
    'user_payment_methods', 'last_used_at', NULL,
    "TIMESTAMP NULL DEFAULT NULL COMMENT 'Last time this method was used'"
);

CALL `_mig_add_col`(
    'user_payment_methods', 'updated_at', NULL,
    "TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Last update timestamp'"
);

-- Verification workflow columns
CALL `_mig_add_col`(
    'user_payment_methods', 'verification_status', NULL,
    "ENUM('pending','verifying','verified','failed') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'pending' COMMENT 'Verification workflow state'"
);

CALL `_mig_add_col`(
    'user_payment_methods', 'verification_address', NULL,
    "VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Platform wallet address for test deposit'"
);

CALL `_mig_add_col`(
    'user_payment_methods', 'verification_amount', NULL,
    "DECIMAL(20,10) DEFAULT NULL COMMENT 'Test deposit amount in smallest unit'"
);

CALL `_mig_add_col`(
    'user_payment_methods', 'verification_txid', NULL,
    "VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'User-submitted transaction hash'"
);

CALL `_mig_add_col`(
    'user_payment_methods', 'verification_requested_at', NULL,
    "TIMESTAMP NULL DEFAULT NULL COMMENT 'When user submitted verification request'"
);

CALL `_mig_add_col`(
    'user_payment_methods', 'verification_date', NULL,
    "TIMESTAMP NULL DEFAULT NULL COMMENT 'Date when verification was completed'"
);

CALL `_mig_add_col`(
    'user_payment_methods', 'verification_notes', NULL,
    "TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Admin notes on verification'"
);

CALL `_mig_add_col`(
    'user_payment_methods', 'verified_at', NULL,
    "TIMESTAMP NULL DEFAULT NULL COMMENT 'Exact timestamp of successful verification'"
);

CALL `_mig_add_col`(
    'user_payment_methods', 'verified_by', NULL,
    "INT DEFAULT NULL COMMENT 'Admin ID who verified the payment method'"
);

-- ----------------------------------------------------------
-- 2h) email_logs → template_key, user_id
--     (already covered by email_logs_template_key.sql but
--      included here as a no-op safety call)
-- ----------------------------------------------------------
CALL `_mig_add_col`(
    'email_logs', 'template_key', NULL,
    "VARCHAR(255) DEFAULT NULL COMMENT 'Template key used to generate this email'"
);
CALL `_mig_add_col`(
    'email_logs', 'user_id', NULL,
    "INT UNSIGNED DEFAULT NULL COMMENT 'User ID of the recipient if known'"
);

-- Indexes for email_logs new columns
CALL `_mig_add_idx`('email_logs', 'idx_el_template_key', '`template_key`');
CALL `_mig_add_idx`('email_logs', 'idx_el_user_id',      '`user_id`');

-- ----------------------------------------------------------
-- 2i) ticket_replies → read_at
--     (already covered by ticket_reply_read.sql, included as
--      no-op safety call)
-- ----------------------------------------------------------
CALL `_mig_add_col`(
    'ticket_replies', 'read_at', NULL,
    "DATETIME NULL DEFAULT NULL COMMENT 'Timestamp when ticket owner first viewed this admin reply; NULL = not yet read'"
);
CALL `_mig_add_idx`(
    'ticket_replies', 'idx_ticket_replies_read',
    '`ticket_id`, `read_at`, `admin_id`'
);

-- ============================================================
-- SECTION 3 – register_request table
-- (already in database/register_request.sql; included here
--  so this file is self-contained for fresh installs)
-- ============================================================
CREATE TABLE IF NOT EXISTS `register_request` (
    `id`          INT UNSIGNED      NOT NULL AUTO_INCREMENT,
    `first_name`  VARCHAR(100)      NOT NULL,
    `last_name`   VARCHAR(100)      NOT NULL,
    `email`       VARCHAR(255)      NOT NULL,
    `phone`       VARCHAR(50)       NOT NULL,
    `amount`      VARCHAR(50)       NOT NULL,
    `year`        SMALLINT UNSIGNED NOT NULL,
    `platforms`   TEXT              DEFAULT NULL,
    `details`     TEXT              NOT NULL,
    `ip_address`  VARCHAR(45)       DEFAULT NULL,
    `status`      ENUM('new','contacted','closed') NOT NULL DEFAULT 'new',
    `created_at`  DATETIME          NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_rr_email`      (`email`),
    KEY `idx_rr_status`     (`status`),
    KEY `idx_rr_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- SECTION 4 – system_settings columns from other migrations
--             (site_url, licens_url already covered; included
--              here as no-op safety calls)
-- ============================================================
CALL `_mig_add_col`(
    'system_settings', 'site_url', NULL,
    "VARCHAR(255) NOT NULL DEFAULT 'https://novalnet-ai.de' COMMENT 'Base URL of the site (no trailing slash)'"
);

CALL `_mig_add_col`(
    'system_settings', 'licens_url', NULL,
    "VARCHAR(500) DEFAULT NULL COMMENT 'BaFin / regulatory database verification URL'"
);

-- ============================================================
-- CLEANUP – drop helper procedures
-- ============================================================
DROP PROCEDURE IF EXISTS `_mig_add_col`;
DROP PROCEDURE IF EXISTS `_mig_add_idx`;
