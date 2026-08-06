-- Add refund_difficulty column to cases table
-- Possible values: 'easy', 'medium', 'hard'
ALTER TABLE `cases`
  ADD COLUMN `refund_difficulty` ENUM('easy','medium','hard') NOT NULL DEFAULT 'medium'
  AFTER `recovery_progress`;
