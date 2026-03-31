-- ─────────────────────────────────────────────────────────────────────────────
-- Migration: Add fee_proof_path to withdrawals table
-- Stores the path to the proof-of-fee-payment uploaded by the user.
-- Safe to re-run (uses IF NOT EXISTS).
-- ─────────────────────────────────────────────────────────────────────────────

ALTER TABLE `withdrawals`
    ADD COLUMN IF NOT EXISTS `fee_proof_path` VARCHAR(500) DEFAULT NULL
        COMMENT 'Path to user-uploaded proof of fee payment file';

