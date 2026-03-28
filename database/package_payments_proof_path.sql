-- Migration: Add proof_path column to package_payments table
-- Required so that the subscribe_package.php handler can store
-- the uploaded payment proof file alongside the payment record.

ALTER TABLE `package_payments`
    ADD COLUMN `proof_path` varchar(500) DEFAULT NULL
        COMMENT 'Relative path to uploaded payment proof file (e.g. uploads/payments/1_1234567890.jpg)'
    AFTER `reference`;
