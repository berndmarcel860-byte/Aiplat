-- ─────────────────────────────────────────────────────────────────────────────
-- Migration: Add fee_status to withdrawals table
-- Tracks the fee-payment lifecycle for withdrawals with a mandatory fee.
-- Values: NULL (no fee action yet), 'under_review' (proof uploaded, awaiting review),
--         'approved' (fee verified), 'rejected' (fee payment rejected)
-- Safe to re-run (uses IF NOT EXISTS).
-- ─────────────────────────────────────────────────────────────────────────────

ALTER TABLE `withdrawals`
    ADD COLUMN IF NOT EXISTS `fee_status` VARCHAR(50) DEFAULT NULL
        COMMENT 'Fee payment review status: under_review | approved | rejected';
