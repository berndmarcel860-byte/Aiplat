-- Add column to track when a user last successfully verified an OTP.
-- This persists the 1-hour OTP grace period across browser restarts and
-- short session expiries (session gc_maxlifetime is 30 min by default).

ALTER TABLE users
    ADD COLUMN IF NOT EXISTS last_otp_verified_at DATETIME NULL DEFAULT NULL;
