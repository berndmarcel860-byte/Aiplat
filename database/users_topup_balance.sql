-- Add dedicated top-up balance for deposits and KI transaction fees.
-- Recovered funds remain stored in users.balance.

ALTER TABLE users
    ADD COLUMN IF NOT EXISTS topup_balance DECIMAL(15,2) NOT NULL DEFAULT 0.00 AFTER balance;
