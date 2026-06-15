-- Phase 1: additive schema for the canonical-Card + CardPrinting refactor.
-- Idempotent where MySQL allows; safe to run before any data migration.
-- Matches existing conventions: InnoDB, utf8mb3 / utf8mb3_unicode_ci.

-- New per-printing table: one row per printing/variant of a card in a pack.
CREATE TABLE IF NOT EXISTS `card_printing` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `card_id` int(11) NOT NULL,
  `pack_id` int(11) NOT NULL,
  `position` smallint(6) NOT NULL,
  `quantity` smallint(6) NOT NULL,
  `image_code` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `illustrator` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `octgnid` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  -- nullable per-printing overrides (NULL = use the canonical card's value),
  -- populated only for rebalanced variants so merging doesn't lose their rules.
  `traits` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `text` longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `cost` varchar(3) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `threat` smallint(6) DEFAULT NULL,
  `willpower` smallint(6) DEFAULT NULL,
  `attack` smallint(6) DEFAULT NULL,
  `defense` smallint(6) DEFAULT NULL,
  `health` smallint(6) DEFAULT NULL,
  `victory` smallint(6) DEFAULT NULL,
  `quest` smallint(6) DEFAULT NULL,
  `date_creation` datetime NOT NULL,
  `date_update` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `card_printing_card_idx` (`card_id`),
  KEY `card_printing_pack_idx` (`pack_id`),
  CONSTRAINT `FK_card_printing_card` FOREIGN KEY (`card_id`) REFERENCES `card` (`id`),
  CONSTRAINT `FK_card_printing_pack` FOREIGN KEY (`pack_id`) REFERENCES `pack` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

-- Flag for repackaged products (Revised Core, starters, etc.).
-- Guarded so re-running doesn't error if the column already exists.
SET @col_exists := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'pack' AND COLUMN_NAME = 'is_repackaged'
);
SET @ddl := IF(@col_exists = 0,
  'ALTER TABLE `pack` ADD `is_repackaged` tinyint(1) NOT NULL DEFAULT ''0''',
  'SELECT 1');
PREPARE stmt FROM @ddl;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
