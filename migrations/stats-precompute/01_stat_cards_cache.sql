-- Cache table for the heavy per-card monthly stats report.
-- Populated off-line by `php app/console app:stats:precompute-cards` (cron);
-- StatController::getStatCardsAction only reads from it. One row per (month, step);
-- payload is the exact JSON the old synchronous action returned.
CREATE TABLE IF NOT EXISTS stat_cards_cache (
  month       VARCHAR(7)        NOT NULL,
  step        TINYINT UNSIGNED  NOT NULL,
  payload     LONGTEXT          NOT NULL,
  computed_at DATETIME          NOT NULL,
  PRIMARY KEY (month, step)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
