-- Phase 9: Drop printing-level columns from the card table.
-- All data (pack association, quantity, illustrator, octgnid) now lives in card_printing.
-- Run AFTER the application is deployed with the Phase 9 code (which delegates those
-- getters through CardPrinting) so the app never reads from these columns again.
--
-- NOTE: MySQL 8 has no `DROP COLUMN IF EXISTS` (that is MariaDB syntax), and DDL
-- auto-commits (the transaction wrapper below is cosmetic). This version is
-- idempotent: it drops only the FK / columns that still exist, so it is safe to
-- re-run and safe to run against a DB where the cleanup partially applied.

START TRANSACTION;

-- Drop the FK constraint on card.pack_id (auto-named by MySQL/MariaDB), if present.
SET @fk := (
    SELECT constraint_name
    FROM information_schema.key_column_usage
    WHERE table_schema = DATABASE()
      AND table_name    = 'card'
      AND column_name   = 'pack_id'
      AND referenced_table_name = 'pack'
    LIMIT 1
);
SET @sql := IF(@fk IS NOT NULL,
    CONCAT('ALTER TABLE card DROP FOREIGN KEY `', @fk, '`'),
    'DO 0 -- no FK to drop'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Drop the now-redundant columns. Build the list from the columns that still
-- exist so re-running (or a partially-applied run) does not error.
SET @drops := (
    SELECT GROUP_CONCAT(CONCAT('DROP COLUMN `', column_name, '`') SEPARATOR ', ')
    FROM information_schema.columns
    WHERE table_schema = DATABASE()
      AND table_name   = 'card'
      AND column_name IN ('pack_id', 'quantity', 'illustrator', 'octgnid')
);
SET @sql := IF(@drops IS NOT NULL,
    CONCAT('ALTER TABLE card ', @drops),
    'DO 0 -- nothing to drop'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

COMMIT;
