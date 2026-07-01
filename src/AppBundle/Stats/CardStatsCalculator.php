<?php

namespace AppBundle\Stats;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\DBAL\Connection;

/**
 * Heavy per-card monthly usage stats, extracted verbatim from the old
 * StatController::getStatCardsAction so the precompute command and the cached
 * controller serve byte-identical JSON. computeCards() scans a whole month of
 * decklistslot/deckslot and MUST run off the request path (the cron command),
 * never on a php-fpm worker -- doing so saturates the shared pool and 504s all
 * three sites.
 */
class CardStatsCalculator {
	/** @var Connection */
	private $conn;

	public function __construct(Registry $doctrine) {
		$this->conn = $doctrine->getConnection();
	}

	/**
	 * Returns the same array StatController::getStatCardsAction used to return
	 * for ($month, $step). $step is '1', '2' or '3'.
	 */
	public function computeCards($month, $step) {
		$dbh = $this->conn;

		// Materialize the primary-printing map once (see controller history):
		// without this MySQL merges the derived table into a per-slot-row
		// correlated subquery + filesort.
		$dbh->executeQuery("SET SESSION optimizer_switch = 'derived_merge=off'");

		// Temp tables live for the whole DB connection, and the precompute command
		// reuses one connection across months/steps -- so drop any left over from a
		// previous computeCards() call before recreating them.
		foreach (['decklist_filtered', 'decklist_motka', 'decklist_contract', 'deck_filtered', 'deck_motka', 'deck_contract',
				  'decklist_filtered2', 'decklist_motka2', 'decklist_contract2', 'deck_filtered2', 'deck_motka2', 'deck_contract2'] as $tmp) {
			$dbh->executeQuery("DROP TEMPORARY TABLE IF EXISTS $tmp");
		}

		if ($step == '1') {
			$query = "CREATE TEMPORARY TABLE decklist_filtered
SELECT CASE WHEN CAST(c.code AS UNSIGNED) > 1000000 THEN SUBSTRING(c.code, 3) ELSE source_code(c.code, cp.name) END AS code,
  dls.quantity,
  dl.id,
  CAST(c.code AS UNSIGNED) > 1000000 AS is_motka
FROM decklist dl
JOIN pack p
ON dl.last_pack_id = p.id
JOIN decklistslot dls
ON dl.id = dls.decklist_id
JOIN card c
ON dls.card_id = c.id
JOIN (SELECT cpx.card_id, cpx.pack_id, cpx.octgnid FROM card_printing cpx WHERE cpx.id = (SELECT cpy.id FROM card_printing cpy JOIN pack py ON py.id = cpy.pack_id WHERE cpy.card_id = cpx.card_id ORDER BY (py.date_release IS NULL), py.date_release, cpy.position, cpy.id LIMIT 1)) cprim ON cprim.card_id = c.id
JOIN pack cp
ON cp.id = cprim.pack_id
WHERE dl.date_creation LIKE '" . $month . "-%'
  AND p.date_release >= '2019-08-02'
  AND (c.cost IS NULL OR c.cost != '-')";
			$dbh->executeQuery($query, []);

			$query = "CREATE TEMPORARY TABLE decklist_motka
SELECT DISTINCT id
  FROM decklist_filtered
  WHERE is_motka";
			$dbh->executeQuery($query, []);

			$query = "CREATE TEMPORARY TABLE decklist_contract
SELECT DISTINCT id
  FROM decklist_filtered
  WHERE code = '22134'";
			$dbh->executeQuery($query, []);

			$query = "CREATE TEMPORARY TABLE deck_filtered
SELECT CASE WHEN CAST(c.code AS UNSIGNED) > 1000000 THEN SUBSTRING(c.code, 3) ELSE source_code(c.code, cp.name) END AS code,
  ds.quantity,
  d.id,
  CAST(c.code AS UNSIGNED) > 1000000 AS is_motka
FROM deck d
LEFT JOIN decklist dl
ON d.id = dl.parent_deck_id
JOIN pack p
ON d.last_pack_id = p.id
JOIN deckslot ds
ON d.id = ds.deck_id
JOIN card c
ON ds.card_id = c.id
JOIN (SELECT cpx.card_id, cpx.pack_id, cpx.octgnid FROM card_printing cpx WHERE cpx.id = (SELECT cpy.id FROM card_printing cpy JOIN pack py ON py.id = cpy.pack_id WHERE cpy.card_id = cpx.card_id ORDER BY (py.date_release IS NULL), py.date_release, cpy.position, cpy.id LIMIT 1)) cprim ON cprim.card_id = c.id
JOIN pack cp
ON cp.id = cprim.pack_id
WHERE dl.parent_deck_id IS NULL
  AND d.last_pack_id IS NOT NULL
  AND d.problem IS NULL
  AND (('" . $month . "' < '2022-07' AND (d.date_update LIKE '" . $month . "-%' OR (d.date_creation LIKE '" . $month . "-%' AND d.date_update >= '2022-08-01'))) OR
       ('" . $month . "' = '2022-07' AND
        (d.date_creation LIKE '" . $month . "-%' OR d.date_update LIKE '" . $month . "-%')) OR
       ('" . $month . "' >= '2022-08' AND d.date_creation LIKE '" . $month . "-%'))
  AND p.date_release >= '2019-08-02'
  AND (c.cost IS NULL OR c.cost != '-')";
			$dbh->executeQuery($query, []);

			$query = "CREATE TEMPORARY TABLE deck_motka
SELECT DISTINCT id
  FROM deck_filtered
  WHERE is_motka";
			$dbh->executeQuery($query, []);

			$query = "CREATE TEMPORARY TABLE deck_contract
SELECT DISTINCT id
  FROM deck_filtered
  WHERE code = '22134'";
			$dbh->executeQuery($query, []);

			$query1 = "SELECT c.code,
  cprim.octgnid,
  c.name,
  t.name AS type,
  s.name AS sphere,
  p.name AS pack,
  p.date_release AS released,
  CASE WHEN c.cost = '-' THEN 'Encounter' ELSE '' END AS encounter,
  COUNT(sl.code) AS full_decks,
  ROUND(COALESCE(AVG(sl.quantity), 0), 2) AS full_deck_copies
FROM card c
JOIN (SELECT cpx.card_id, cpx.pack_id, cpx.octgnid FROM card_printing cpx WHERE cpx.id = (SELECT cpy.id FROM card_printing cpy JOIN pack py ON py.id = cpy.pack_id WHERE cpy.card_id = cpx.card_id ORDER BY (py.date_release IS NULL), py.date_release, cpy.position, cpy.id LIMIT 1)) cprim ON cprim.card_id = c.id
JOIN pack p
ON p.id = cprim.pack_id
JOIN type t
ON c.type_id = t.id
JOIN sphere s
ON c.sphere_id = s.id
LEFT JOIN (
  SELECT code,
    LEAST(SUM(quantity), 3) AS quantity
  FROM decklist_filtered
  GROUP BY id, code

  UNION ALL

  SELECT '22134' AS code,
    1 AS quantity
  FROM decklist_motka dm
  LEFT JOIN decklist_contract dc
  ON dm.id = dc.id
  WHERE dc.id IS NULL

  UNION ALL

  SELECT code,
    LEAST(SUM(quantity), 3) AS quantity
  FROM deck_filtered
  GROUP BY id, code

  UNION ALL

  SELECT '22134' AS code,
    1 AS quantity
  FROM deck_motka dm
  LEFT JOIN deck_contract dc
  ON dm.id = dc.id
  WHERE dc.id IS NULL
) sl
ON c.code = sl.code
WHERE t.name != 'Campaign'
  AND s.name NOT IN ('Baggins', 'Fellowship')
  AND p.name NOT IN ('Messenger of the King Allies', 'ALeP - Messenger of the King Allies', 'Two-Player Limited Edition Starter',
                     'Dwarves of Durin', 'Elves of Lórien', 'Defenders of Gondor', 'Riders of Rohan')
  AND CAST(p.date_release AS CHAR) <= '" . $month . "-31'
GROUP BY c.code
ORDER BY encounter, full_decks DESC, CAST(c.code AS UNSIGNED) DESC";
			$cards = $dbh->executeQuery($query1, [])->fetchAll(\PDO::FETCH_ASSOC);

			$res = ['cards' => $cards];
			return $res;
		}

		if ($step == '2') {
			$query = "CREATE TEMPORARY TABLE decklist_filtered2
SELECT CASE WHEN CAST(c.code AS UNSIGNED) > 1000000 THEN SUBSTRING(c.code, 3) ELSE source_code(c.code, cp.name) END AS code,
  dls.quantity,
  dl.id,
  CAST(c.code AS UNSIGNED) > 1000000 AS is_motka
FROM decklist dl
JOIN pack p
ON dl.last_pack_id = p.id
JOIN decklistslot dls
ON dl.id = dls.decklist_id
JOIN card c
ON dls.card_id = c.id
JOIN (SELECT cpx.card_id, cpx.pack_id, cpx.octgnid FROM card_printing cpx WHERE cpx.id = (SELECT cpy.id FROM card_printing cpy JOIN pack py ON py.id = cpy.pack_id WHERE cpy.card_id = cpx.card_id ORDER BY (py.date_release IS NULL), py.date_release, cpy.position, cpy.id LIMIT 1)) cprim ON cprim.card_id = c.id
JOIN pack cp
ON cp.id = cprim.pack_id
WHERE dl.date_creation LIKE '" . $month . "-%'
  AND p.date_release < '2019-08-02'
  AND (c.cost IS NULL OR c.cost != '-')";
			$dbh->executeQuery($query, []);

			$query = "CREATE TEMPORARY TABLE decklist_motka2
SELECT DISTINCT id
  FROM decklist_filtered2
  WHERE is_motka";
			$dbh->executeQuery($query, []);

			$query = "CREATE TEMPORARY TABLE decklist_contract2
SELECT DISTINCT id
  FROM decklist_filtered2
  WHERE code = '22134'";
			$dbh->executeQuery($query, []);

			$query = "CREATE TEMPORARY TABLE deck_filtered2
SELECT CASE WHEN CAST(c.code AS UNSIGNED) > 1000000 THEN SUBSTRING(c.code, 3) ELSE source_code(c.code, cp.name) END AS code,
  ds.quantity,
  d.id,
  CAST(c.code AS UNSIGNED) > 1000000 AS is_motka
FROM deck d
LEFT JOIN decklist dl
ON d.id = dl.parent_deck_id
JOIN pack p
ON d.last_pack_id = p.id
JOIN deckslot ds
ON d.id = ds.deck_id
JOIN card c
ON ds.card_id = c.id
JOIN (SELECT cpx.card_id, cpx.pack_id, cpx.octgnid FROM card_printing cpx WHERE cpx.id = (SELECT cpy.id FROM card_printing cpy JOIN pack py ON py.id = cpy.pack_id WHERE cpy.card_id = cpx.card_id ORDER BY (py.date_release IS NULL), py.date_release, cpy.position, cpy.id LIMIT 1)) cprim ON cprim.card_id = c.id
JOIN pack cp
ON cp.id = cprim.pack_id
WHERE dl.parent_deck_id IS NULL
  AND d.last_pack_id IS NOT NULL
  AND d.problem IS NULL
  AND (('" . $month . "' < '2022-07' AND (d.date_update LIKE '" . $month . "-%' OR (d.date_creation LIKE '" . $month . "-%' AND d.date_update >= '2022-08-01'))) OR
       ('" . $month . "' = '2022-07' AND
        (d.date_creation LIKE '" . $month . "-%' OR d.date_update LIKE '" . $month . "-%')) OR
       ('" . $month . "' >= '2022-08' AND d.date_creation LIKE '" . $month . "-%'))
  AND p.date_release < '2019-08-02'
  AND (c.cost IS NULL OR c.cost != '-')";
			$dbh->executeQuery($query, []);

			$query = "CREATE TEMPORARY TABLE deck_motka2
SELECT DISTINCT id
  FROM deck_filtered2
  WHERE is_motka";
			$dbh->executeQuery($query, []);

			$query = "CREATE TEMPORARY TABLE deck_contract2
SELECT DISTINCT id
  FROM deck_filtered2
  WHERE code = '22134'";
			$dbh->executeQuery($query, []);

			$query2 = "SELECT c.code,
    COUNT(sl.code) AS limited_decks,
    ROUND(COALESCE(AVG(sl.quantity), 0), 2) AS limited_deck_copies
FROM card c
JOIN (SELECT cpx.card_id, cpx.pack_id, cpx.octgnid FROM card_printing cpx WHERE cpx.id = (SELECT cpy.id FROM card_printing cpy JOIN pack py ON py.id = cpy.pack_id WHERE cpy.card_id = cpx.card_id ORDER BY (py.date_release IS NULL), py.date_release, cpy.position, cpy.id LIMIT 1)) cprim ON cprim.card_id = c.id
JOIN pack p
ON p.id = cprim.pack_id
JOIN type t
ON c.type_id = t.id
JOIN sphere s
ON c.sphere_id = s.id
LEFT JOIN (
  SELECT code,
    LEAST(SUM(quantity), 3) AS quantity
  FROM decklist_filtered2
  GROUP BY id, code

  UNION ALL

  SELECT '22134' AS code,
    1 AS quantity
  FROM decklist_motka2 dm
  LEFT JOIN decklist_contract2 dc
  ON dm.id = dc.id
  WHERE dc.id IS NULL

  UNION ALL

  SELECT code,
    LEAST(SUM(quantity), 3) AS quantity
  FROM deck_filtered2
  GROUP BY id, code

  UNION ALL

  SELECT '22134' AS code,
    1 AS quantity
  FROM deck_motka2 dm
  LEFT JOIN deck_contract2 dc
  ON dm.id = dc.id
  WHERE dc.id IS NULL
) sl
ON c.code = sl.code
WHERE t.name != 'Campaign'
  AND s.name NOT IN ('Baggins', 'Fellowship')
  AND p.name NOT IN ('Messenger of the King Allies', 'ALeP - Messenger of the King Allies', 'Two-Player Limited Edition Starter',
                     'Dwarves of Durin', 'Elves of Lórien', 'Defenders of Gondor', 'Riders of Rohan')
  AND CAST(p.date_release AS CHAR) <= '" . $month . "-31'
GROUP BY c.code
ORDER BY c.code";
			$cards = $dbh->executeQuery($query2, [])->fetchAll(\PDO::FETCH_ASSOC);

			$res = ['cards' => $cards];
			return $res;
		}

		if ($step == '3') {
			$query3 = "SELECT c.code,
    COUNT(sl.new_code) AS sides,
    ROUND(COALESCE(AVG(sl.quantity), 0), 2) AS side_copies
  FROM card c
  JOIN (SELECT cpx.card_id, cpx.pack_id, cpx.octgnid FROM card_printing cpx WHERE cpx.id = (SELECT cpy.id FROM card_printing cpy JOIN pack py ON py.id = cpy.pack_id WHERE cpy.card_id = cpx.card_id ORDER BY (py.date_release IS NULL), py.date_release, cpy.position, cpy.id LIMIT 1)) cprim ON cprim.card_id = c.id
  JOIN pack p
  ON p.id = cprim.pack_id
  JOIN type t
  ON c.type_id = t.id
  JOIN sphere s
  ON c.sphere_id = s.id
  LEFT JOIN (
    SELECT code AS new_code,
      LEAST(SUM(quantity), 3) AS quantity
    FROM (
    SELECT source_code(c.code, cp.name) AS code,
      dls.quantity,
      dl.id
    FROM decklist dl
    JOIN pack p
    ON dl.last_pack_id = p.id
    JOIN decklistsideslot dls
    ON dl.id = dls.decklist_id
    JOIN card c
    ON dls.card_id = c.id
    JOIN (SELECT cpx.card_id, cpx.pack_id, cpx.octgnid FROM card_printing cpx WHERE cpx.id = (SELECT cpy.id FROM card_printing cpy JOIN pack py ON py.id = cpy.pack_id WHERE cpy.card_id = cpx.card_id ORDER BY (py.date_release IS NULL), py.date_release, cpy.position, cpy.id LIMIT 1)) cprim ON cprim.card_id = c.id
    JOIN pack cp
    ON cp.id = cprim.pack_id
    WHERE dl.date_creation LIKE '" . $month . "-%'

    UNION ALL

    SELECT c.code,
      dls.quantity,
      dl.id
    FROM decklist dl
    JOIN pack p
    ON dl.last_pack_id = p.id
    JOIN decklistslot dls
    ON dl.id = dls.decklist_id
    JOIN card c
    ON dls.card_id = c.id
    WHERE dl.date_creation LIKE '" . $month . "-%'
      AND c.cost = '-'
    ) t
    GROUP BY id, code

    UNION ALL

    SELECT code AS new_code,
      LEAST(SUM(quantity), 3) AS quantity
    FROM (
    SELECT source_code(c.code, cp.name) AS code,
      ds.quantity,
      d.id
    FROM deck d
    LEFT JOIN decklist dl
    ON d.id = dl.parent_deck_id
    JOIN pack p
    ON d.last_pack_id = p.id
    JOIN decksideslot ds
    ON d.id = ds.deck_id
    JOIN card c
    ON ds.card_id = c.id
    JOIN (SELECT cpx.card_id, cpx.pack_id, cpx.octgnid FROM card_printing cpx WHERE cpx.id = (SELECT cpy.id FROM card_printing cpy JOIN pack py ON py.id = cpy.pack_id WHERE cpy.card_id = cpx.card_id ORDER BY (py.date_release IS NULL), py.date_release, cpy.position, cpy.id LIMIT 1)) cprim ON cprim.card_id = c.id
    JOIN pack cp
    ON cp.id = cprim.pack_id
    WHERE dl.parent_deck_id IS NULL
      AND d.last_pack_id IS NOT NULL
      AND d.problem IS NULL
      AND (('" . $month . "' < '2022-07' AND (d.date_update LIKE '" . $month . "-%' OR (d.date_creation LIKE '" . $month . "-%' AND d.date_update >= '2022-08-01'))) OR
           ('" . $month . "' = '2022-07' AND
            (d.date_creation LIKE '" . $month . "-%' OR d.date_update LIKE '" . $month . "-%')) OR
           ('" . $month . "' >= '2022-08' AND d.date_creation LIKE '" . $month . "-%'))

    UNION ALL

    SELECT c.code,
      ds.quantity,
      d.id
    FROM deck d
    LEFT JOIN decklist dl
    ON d.id = dl.parent_deck_id
    JOIN pack p
    ON d.last_pack_id = p.id
    JOIN deckslot ds
    ON d.id = ds.deck_id
    JOIN card c
    ON ds.card_id = c.id
    WHERE dl.parent_deck_id IS NULL
      AND d.last_pack_id IS NOT NULL
      AND d.problem IS NULL
      AND (('" . $month . "' < '2022-07' AND (d.date_update LIKE '" . $month . "-%' OR (d.date_creation LIKE '" . $month . "-%' AND d.date_update >= '2022-08-01'))) OR
           ('" . $month . "' = '2022-07' AND
            (d.date_creation LIKE '" . $month . "-%' OR d.date_update LIKE '" . $month . "-%')) OR
           ('" . $month . "' >= '2022-08' AND d.date_creation LIKE '" . $month . "-%'))
      AND c.cost = '-'
    ) t
    GROUP BY id, code
  ) sl
  ON c.code = sl.new_code
  WHERE t.name != 'Campaign'
    AND s.name NOT IN ('Baggins', 'Fellowship')
    AND p.name NOT IN ('Messenger of the King Allies', 'ALeP - Messenger of the King Allies', 'Two-Player Limited Edition Starter',
                       'Dwarves of Durin', 'Elves of Lórien', 'Defenders of Gondor', 'Riders of Rohan')
    AND CAST(p.date_release AS CHAR) <= '" . $month . "-31'
  GROUP BY c.code
  ORDER BY c.code";
			$cards = $dbh->executeQuery($query3, [])->fetchAll(\PDO::FETCH_ASSOC);

			$query_total = "SELECT full_decks,
  limited_decks,
  full_decks + limited_decks AS sides
FROM (
  SELECT (SELECT COUNT(*)
    FROM decklist dl
    JOIN pack p
    ON dl.last_pack_id = p.id
    WHERE dl.date_creation LIKE '" . $month . "-%'
      AND p.date_release >= '2019-08-02'
    ) + (
    SELECT COUNT(*)
    FROM deck d
    LEFT JOIN decklist dl
    ON d.id = dl.parent_deck_id
    JOIN pack p
    ON d.last_pack_id = p.id
    WHERE dl.parent_deck_id IS NULL
      AND d.last_pack_id IS NOT NULL
      AND d.problem IS NULL
      AND (('" . $month . "' < '2022-07' AND (d.date_update LIKE '" . $month . "-%' OR (d.date_creation LIKE '" . $month . "-%' AND d.date_update >= '2022-08-01'))) OR
           ('" . $month . "' = '2022-07' AND
            (d.date_creation LIKE '" . $month . "-%' OR d.date_update LIKE '" . $month . "-%')) OR
           ('" . $month . "' >= '2022-08' AND d.date_creation LIKE '" . $month . "-%'))
      AND p.date_release >= '2019-08-02'
    ) AS full_decks,
    (SELECT COUNT(*)
    FROM decklist dl
    JOIN pack p
    ON dl.last_pack_id = p.id
    WHERE dl.date_creation LIKE '" . $month . "-%'
      AND p.date_release < '2019-08-02'
    ) + (
    SELECT COUNT(*)
    FROM deck d
    LEFT JOIN decklist dl
    ON d.id = dl.parent_deck_id
    JOIN pack p
    ON d.last_pack_id = p.id
    WHERE dl.parent_deck_id IS NULL
      AND d.last_pack_id IS NOT NULL
      AND d.problem IS NULL
      AND (('" . $month . "' < '2022-07' AND (d.date_update LIKE '" . $month . "-%' OR (d.date_creation LIKE '" . $month . "-%' AND d.date_update >= '2022-08-01'))) OR
           ('" . $month . "' = '2022-07' AND
            (d.date_creation LIKE '" . $month . "-%' OR d.date_update LIKE '" . $month . "-%')) OR
           ('" . $month . "' >= '2022-08' AND d.date_creation LIKE '" . $month . "-%'))
      AND p.date_release < '2019-08-02'
    ) AS limited_decks
  ) t";
			$total = $dbh->executeQuery($query_total, [])->fetchAll(\PDO::FETCH_ASSOC);

			$packs = $this->getPacks();
			$pack_rules = $this->getPackRuless();
			$mapping = $this->getOctgnIdMapping();

			$res = ['cards' => $cards,
					'total' => $total[0],
					'packs' => $packs,
					'pack_rules' => $pack_rules,
					'mapping' => $mapping];
			return $res;
		}

		return null;
	}

	private function getPacks() {
		$dbh = $this->conn;

		$query = "SELECT name, date_release
FROM pack
WHERE date_release IS NOT NULL
ORDER BY date_release";
		$packs = $dbh->executeQuery($query, [])->fetchAll(\PDO::FETCH_ASSOC);
		return $packs;
	}

	private function getPackRuless() {
		$pack_rules = ['Core Set' => ['2000-01-01', '2011-07-21'],
						'Shadows of Mirkwood' => ['2011-07-21', '2012-01-06'],
						'Dwarrowdelf' => ['2012-01-06', '2012-08-17'],
						'Against the Shadow' => ['2012-08-17', '2014-02-21'],
						'The Ring-maker' => ['2014-02-21', '2015-04-03'],
						'Angmar Awakened' => ['2015-04-03', '2016-02-11'],
						'Dream-chaser' => ['2016-02-11', '2016-11-23'],
						'Haradrim' => ['2016-11-23', '2018-06-14'],
						'Ered Mithrin' => ['2018-06-14', '2019-08-02'],
						'Vengeance of Mordor' => ['2019-08-02', '2021-03-21'],
						'ALeP - Oaths of the Rohirrim' => ['2021-03-21', '2099-12-31']];
		return $pack_rules;
	}

	private function getOctgnIdMapping() {
		$dbh = $this->conn;

		$query = "SELECT cprim1.octgnid AS id1,
  cprim2.octgnid AS id2
FROM card c1
JOIN (SELECT cpx.card_id, cpx.pack_id, cpx.octgnid FROM card_printing cpx WHERE cpx.id = (SELECT cpy.id FROM card_printing cpy JOIN pack py ON py.id = cpy.pack_id WHERE cpy.card_id = cpx.card_id ORDER BY (py.date_release IS NULL), py.date_release, cpy.position, cpy.id LIMIT 1)) cprim1 ON cprim1.card_id = c1.id
JOIN pack p
ON p.id = cprim1.pack_id
JOIN card c2
ON source_code(c1.code, p.name) = c2.code
JOIN (SELECT cpx.card_id, cpx.pack_id, cpx.octgnid FROM card_printing cpx WHERE cpx.id = (SELECT cpy.id FROM card_printing cpy JOIN pack py ON py.id = cpy.pack_id WHERE cpy.card_id = cpx.card_id ORDER BY (py.date_release IS NULL), py.date_release, cpy.position, cpy.id LIMIT 1)) cprim2 ON cprim2.card_id = c2.id
WHERE c1.code != c2.code
ORDER BY CAST(c1.code AS UNSIGNED)";
		$res = $dbh->executeQuery($query, [])->fetchAll(\PDO::FETCH_ASSOC);
		$mapping = [];
		for ($i = 0; $i < count($res); $i++) {
			$mapping[$res[$i]['id1']] = $res[$i]['id2'];
		}
		return $mapping;
	}
}
