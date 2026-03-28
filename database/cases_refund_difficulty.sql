-- Add refund_difficulty column to cases table
-- Possible values: 'easy', 'medium', 'hard'
-- Safe to run even if migration was already applied
ALTER TABLE `cases`
  ADD COLUMN IF NOT EXISTS `refund_difficulty` ENUM('easy','medium','hard') NOT NULL DEFAULT 'medium'
  AFTER `recovery_progress`;
