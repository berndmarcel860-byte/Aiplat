-- =============================================================
-- Satoshi Tests Table
-- Run this migration to create the satoshi_tests table used
-- by satoshi-test.php and the Satoshi verification helpers.
-- =============================================================

CREATE TABLE IF NOT EXISTS `satoshi_tests` (
    `id`                INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id`           INT UNSIGNED NOT NULL,
    `amount`            DECIMAL(15,2) NOT NULL DEFAULT 10.00  COMMENT 'Test payment amount sent by user',
    `currency`          VARCHAR(10)  NOT NULL DEFAULT 'EUR',
    `payment_method`    VARCHAR(50)  NOT NULL DEFAULT 'bank'  COMMENT 'bank | crypto | other',
    `crypto_coin`       VARCHAR(20)  NULL                     COMMENT 'BTC / ETH / USDT … when payment_method=crypto',
    `tx_reference`      VARCHAR(255) NULL                     COMMENT 'Transaction ID or bank reference provided by user',
    `status`            ENUM('pending','under_review','verified','confirmed','completed','rejected','failed')
                        NOT NULL DEFAULT 'pending',
    `admin_notes`       TEXT         NULL,
    `verified_at`       DATETIME     NULL,
    `created_at`        DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`        DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_satoshi_user`        (`user_id`),
    INDEX `idx_satoshi_status`      (`status`),
    INDEX `idx_satoshi_created`     (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
