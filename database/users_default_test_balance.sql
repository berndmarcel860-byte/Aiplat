-- Keep recovered funds at 0 by default and give new users a dedicated top-up test balance.
-- Existing rows are not modified.

ALTER TABLE users
    MODIFY COLUMN balance DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    MODIFY COLUMN topup_balance DECIMAL(15,2) NOT NULL DEFAULT 5.00;
