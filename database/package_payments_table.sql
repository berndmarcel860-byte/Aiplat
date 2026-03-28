-- =====================================================================
-- Migration: Create package_payments table
-- Purpose: Dedicated table to track package purchase payments,
--          separate from general user deposit/withdrawal transactions.
-- =====================================================================

CREATE TABLE IF NOT EXISTS `package_payments` (
  `id`               int          NOT NULL AUTO_INCREMENT,
  `user_package_id`  int          NOT NULL COMMENT 'FK → user_packages.id',
  `user_id`          int          NOT NULL COMMENT 'Denormalised FK → users.id',
  `package_id`       int          NOT NULL COMMENT 'Denormalised FK → packages.id',
  `amount`           decimal(10,2) NOT NULL DEFAULT 0.00,
  `currency`         varchar(10)  NOT NULL DEFAULT 'EUR',
  `payment_method`   varchar(50)  DEFAULT NULL COMMENT 'e.g. bank_transfer, bitcoin',
  `reference`        varchar(100) DEFAULT NULL COMMENT 'Payment reference / transaction hash',
  `status`           enum('pending','completed','failed','refunded') NOT NULL DEFAULT 'pending',
  `admin_notes`      text         DEFAULT NULL,
  `processed_by`     int          DEFAULT NULL COMMENT 'FK → admins.id',
  `processed_at`     datetime     DEFAULT NULL,
  `created_at`       datetime     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`       datetime     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_pp_user_id`         (`user_id`),
  KEY `idx_pp_user_package_id` (`user_package_id`),
  KEY `idx_pp_package_id`      (`package_id`),
  KEY `idx_pp_status`          (`status`),
  KEY `idx_pp_created_at`      (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci
  COMMENT='Dedicated package purchase payment records';

-- Backfill: migrate existing PKG-/SUB- transactions from the legacy
-- transactions table into the new package_payments table.
-- Only migrates records where a matching user_packages row can be found.
INSERT IGNORE INTO `package_payments`
    (`user_package_id`, `user_id`, `package_id`, `amount`, `payment_method`,
     `reference`, `status`, `admin_notes`, `created_at`, `updated_at`)
SELECT
    up.id                                                     AS user_package_id,
    t.user_id,
    up.package_id                                             AS package_id,
    t.amount,
    pm.method_name                                            AS payment_method,
    t.reference,
    CASE t.status
        WHEN 'completed' THEN 'completed'
        WHEN 'failed'    THEN 'failed'
        ELSE                  'pending'
    END                                                       AS status,
    t.admin_notes,
    t.created_at,
    t.updated_at
FROM `transactions` t
LEFT JOIN `payment_methods` pm  ON t.payment_method_id = pm.id
-- Match to the most recent user_packages row created on or before this transaction
INNER JOIN `user_packages` up
    ON  up.user_id    = t.user_id
    AND up.created_at = (
        SELECT MAX(up2.created_at)
        FROM   `user_packages` up2
        WHERE  up2.user_id    = t.user_id
          AND  up2.created_at <= t.created_at
    )
WHERE (t.reference LIKE 'PKG-%' OR t.reference LIKE 'SUB-%')
  AND NOT EXISTS (
      SELECT 1 FROM `package_payments` pp
      WHERE pp.reference = t.reference
  );
