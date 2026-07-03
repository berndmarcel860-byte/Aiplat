-- KI Dashboard scan entries – admin-managed per-user entries
-- Entry types: ai_search, platform_check, reported_platform
-- Run once to enable the KI Dashboard feature.

CREATE TABLE IF NOT EXISTS ki_scan_entries (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    entry_type ENUM('ai_search','platform_check','reported_platform') NOT NULL DEFAULT 'ai_search',
    title VARCHAR(255) NOT NULL,
    description TEXT,
    platform_name VARCHAR(255) DEFAULT NULL,
    platform_url VARCHAR(500) DEFAULT NULL,
    kyc_status ENUM('verified','pending','not_required','failed') NOT NULL DEFAULT 'not_required',
    status ENUM('scanning','found','not_found','verified','flagged','resolved') NOT NULL DEFAULT 'scanning',
    fee_find_amount DECIMAL(15,2) NOT NULL DEFAULT 0.00 COMMENT 'Cost to find the transaction',
    fee_recover_amount DECIMAL(15,2) NOT NULL DEFAULT 0.00 COMMENT 'Cost to recover the funds',
    transaction_hash VARCHAR(500) DEFAULT NULL,
    blockchain_network VARCHAR(100) DEFAULT NULL,
    risk_level ENUM('low','medium','high','critical') NOT NULL DEFAULT 'medium',
    is_visible TINYINT(1) NOT NULL DEFAULT 1,
    admin_notes TEXT DEFAULT NULL,
    created_by_admin INT DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id),
    INDEX idx_entry_type (entry_type),
    INDEX idx_created_at (created_at),
    INDEX idx_is_visible (is_visible)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
