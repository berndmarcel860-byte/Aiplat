-- Track when a user has read an admin reply on a support ticket.
-- read_at is set to the current timestamp the first time the user
-- opens the ticket conversation while the admin reply exists.
ALTER TABLE ticket_replies
    ADD COLUMN read_at DATETIME NULL DEFAULT NULL
        COMMENT 'Timestamp when the ticket owner first viewed this admin reply; NULL = not yet read';

-- Index to speed up the UPDATE that marks replies as read:
-- WHERE ticket_id = ? AND admin_id IS NOT NULL AND read_at IS NULL
-- Putting read_at first lets MySQL skip already-read rows cheaply.
CREATE INDEX idx_ticket_replies_read ON ticket_replies (ticket_id, read_at, admin_id);
