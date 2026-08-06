-- Migration: fix broken FK constraint on email_logs.template_id
--
-- The email_logs table was created with a foreign key referencing
-- `email_templates112` (a non-existent legacy table name). This causes a
-- fatal PDOException (SQLSTATE 23000) every time a template email is logged
-- because the inserted template_id cannot be found in email_templates112.
--
-- This migration:
--   1. Drops all broken FK constraints on email_logs.template_id that
--      reference any table other than email_templates.
--   2. Adds the correct FK constraint referencing email_templates(id)
--      with ON DELETE SET NULL so that deleting a template does not
--      orphan log rows.
--
-- Safe to run multiple times via stored procedure guard.
-- Compatible with MySQL 5.7+ and MariaDB.

DROP PROCEDURE IF EXISTS `_fix_email_logs_fk`;

DELIMITER $$
CREATE PROCEDURE `_fix_email_logs_fk`()
BEGIN
    DECLARE done INT DEFAULT 0;
    DECLARE fk_name VARCHAR(255);
    DECLARE cur CURSOR FOR
        SELECT k.CONSTRAINT_NAME
        FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE k
        JOIN INFORMATION_SCHEMA.TABLE_CONSTRAINTS tc
            ON tc.CONSTRAINT_NAME = k.CONSTRAINT_NAME
           AND tc.TABLE_SCHEMA    = k.TABLE_SCHEMA
           AND tc.TABLE_NAME      = k.TABLE_NAME
        WHERE k.TABLE_SCHEMA     = DATABASE()
          AND k.TABLE_NAME       = 'email_logs'
          AND k.COLUMN_NAME      = 'template_id'
          AND tc.CONSTRAINT_TYPE = 'FOREIGN KEY'
          AND (k.REFERENCED_TABLE_NAME IS NULL
               OR k.REFERENCED_TABLE_NAME != 'email_templates');
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = 1;

    -- Step 1: drop all broken FK constraints on email_logs.template_id
    OPEN cur;
    read_loop: LOOP
        FETCH cur INTO fk_name;
        IF done THEN
            LEAVE read_loop;
        END IF;
        SET @drop_fk = CONCAT('ALTER TABLE `email_logs` DROP FOREIGN KEY `', fk_name, '`');
        PREPARE stmt FROM @drop_fk;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END LOOP;
    CLOSE cur;

    -- Step 2: ensure template_id is nullable (required for ON DELETE SET NULL)
    IF EXISTS (
        SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME   = 'email_logs'
          AND COLUMN_NAME  = 'template_id'
          AND IS_NULLABLE  = 'NO'
    ) THEN
        ALTER TABLE `email_logs` MODIFY COLUMN `template_id` INT DEFAULT NULL;
    END IF;

    -- Step 3: add the correct FK if it does not already exist
    IF NOT EXISTS (
        SELECT 1
        FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE k
        JOIN INFORMATION_SCHEMA.TABLE_CONSTRAINTS tc
            ON tc.CONSTRAINT_NAME = k.CONSTRAINT_NAME
           AND tc.TABLE_SCHEMA    = k.TABLE_SCHEMA
           AND tc.TABLE_NAME      = k.TABLE_NAME
        WHERE k.TABLE_SCHEMA           = DATABASE()
          AND k.TABLE_NAME             = 'email_logs'
          AND k.COLUMN_NAME            = 'template_id'
          AND tc.CONSTRAINT_TYPE       = 'FOREIGN KEY'
          AND k.REFERENCED_TABLE_NAME  = 'email_templates'
    ) THEN
        ALTER TABLE `email_logs`
            ADD CONSTRAINT `email_logs_ibfk_1`
            FOREIGN KEY (`template_id`)
            REFERENCES `email_templates` (`id`)
            ON DELETE SET NULL;
    END IF;
END$$
DELIMITER ;

CALL `_fix_email_logs_fk`();
DROP PROCEDURE IF EXISTS `_fix_email_logs_fk`;

