ALTER TABLE user_custom_pack ADD COLUMN is_published TINYINT(1) NOT NULL DEFAULT 0 AFTER is_enabled;
