-- Escrow accounts table: tracks escrow holding for each deposit
-- Run this migration to enable the escrow system on deposits

CREATE TABLE IF NOT EXISTS `escrow_accounts` (
  `id` int NOT NULL AUTO_INCREMENT,
  `deposit_id` int NOT NULL COMMENT 'References deposits.id',
  `user_id` int NOT NULL COMMENT 'References users.id',
  `amount` decimal(15,2) NOT NULL,
  `reference` varchar(80) NOT NULL COMMENT 'Unique escrow reference e.g. ESC-XXXXXX',
  `status` enum('holding','verified','released','refunded','cancelled') NOT NULL DEFAULT 'holding',
  `held_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'When funds entered escrow',
  `released_at` datetime DEFAULT NULL COMMENT 'When funds were released to user account',
  `released_by` int DEFAULT NULL COMMENT 'Admin user id who released the funds',
  `notes` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_escrow_deposit` (`deposit_id`),
  UNIQUE KEY `uq_escrow_reference` (`reference`),
  KEY `idx_escrow_user` (`user_id`),
  KEY `idx_escrow_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
