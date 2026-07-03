-- Set a default test balance for newly created users.
-- Existing balances are not modified.

ALTER TABLE users
    MODIFY COLUMN balance DECIMAL(15,2) NOT NULL DEFAULT 5.00;
