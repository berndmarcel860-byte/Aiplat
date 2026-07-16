-- Migration: per-case legal-milestone visibility flags
-- Steps 2, 3, 4 are hidden by default; only Step 1 (Fallaufnahme) is always visible.
-- Admin enables each step individually from the Case Management back-end.

CREATE TABLE IF NOT EXISTS `case_milestone_visibility` (
    `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `case_id`     INT UNSIGNED NOT NULL,
    `step2`       TINYINT(1)   NOT NULL DEFAULT 0 COMMENT 'Forderungsschreiben visible',
    `step3`       TINYINT(1)   NOT NULL DEFAULT 0 COMMENT 'Regulatorische Eskalation visible',
    `step4`       TINYINT(1)   NOT NULL DEFAULT 0 COMMENT 'Rückerstattung / Laufende Verhandlungen visible',
    `updated_by`  INT          NULL     DEFAULT NULL COMMENT 'admin id who last changed',
    `updated_at`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_case` (`case_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
