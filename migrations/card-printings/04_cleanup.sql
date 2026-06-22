-- Phase 9: Drop printing-level columns from the card table.
-- All data (pack association, quantity, illustrator, octgnid) now lives in card_printing.
-- Run AFTER the application is deployed with the Phase 9 code (which delegates those
-- getters through CardPrinting) so the app never reads from these columns again.

START TRANSACTION;

-- Drop the FK constraint on card.pack_id (auto-named by MySQL/MariaDB).
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
    'SELECT 1 -- no FK to drop'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Drop the now-redundant columns.
ALTER TABLE card
    DROP COLUMN IF EXISTS pack_id,
    DROP COLUMN IF EXISTS quantity,
    DROP COLUMN IF EXISTS illustrator,
    DROP COLUMN IF EXISTS octgnid;

COMMIT;
