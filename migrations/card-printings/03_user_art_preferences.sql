-- Phase 8: per-user alt-art preference (JSON map: card code => preferred pack code).
-- Also expands owned_packs from varchar(512) to TEXT (needed once packs[] tokens exceed 512 chars).
-- Additive, idempotent; run after the app is updated.

ALTER TABLE `user` MODIFY COLUMN `owned_packs` TEXT NULL;
SET @col_exists := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'user' AND COLUMN_NAME = 'art_preferences'
);
SET @ddl := IF(@col_exists = 0,
  'ALTER TABLE `user` ADD `art_preferences` longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL',
  'SELECT 1');
PREPARE stmt FROM @ddl;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
