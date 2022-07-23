<?php
namespace AppBundle\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class StatController extends Controller {
	public function getStatAction(Request $request) {
		$month = $request->query->get('month');
		if (!$month) {
			$month = date('Y-m', strtotime('first day of last month'));
		}

		$packs = $this->getPacks();
		$pack_rules = $this->getPackRuless();

        /* @var $dbh \Doctrine\DBAL\Connection */
        $dbh = $this->getDoctrine()->getConnection();

		$query = "SELECT '" . $month . "' AS month,
  c.cycle,
  IFNULL(d.number, 0) AS number_decks,
  IFNULL(u.number, 0) AS number_users
FROM (
  SELECT 'Core Set' AS cycle
  UNION
  SELECT 'Shadows of Mirkwood' AS cycle
  UNION
  SELECT 'Dwarrowdelf' AS cycle
  UNION
  SELECT 'Against the Shadow' AS cycle
  UNION
  SELECT 'The Ring-maker' AS cycle
  UNION
  SELECT 'Angmar Awakened' AS cycle
  UNION
  SELECT 'Dream-chaser' AS cycle
  UNION
  SELECT 'Haradrim' AS cycle
  UNION
  SELECT 'Ered Mithrin' AS cycle
  UNION
  SELECT 'Vengeance of Mordor' AS cycle
  UNION
  SELECT 'ALeP - Oaths of the Rohirrim' AS cycle
) c
LEFT JOIN (
  SELECT CASE
      WHEN p.date_release < '2011-07-21' THEN 'Core Set'
      WHEN p.date_release >= '2011-07-21' and p.date_release < '2012-01-06' THEN 'Shadows of Mirkwood'
      WHEN p.date_release >= '2012-01-06' and p.date_release < '2012-08-17' THEN 'Dwarrowdelf'
      WHEN p.date_release >= '2012-08-17' and p.date_release < '2014-02-21' THEN 'Against the Shadow'
      WHEN p.date_release >= '2014-02-21' and p.date_release < '2015-04-03' THEN 'The Ring-maker'
      WHEN p.date_release >= '2015-04-03' and p.date_release < '2016-02-11' THEN 'Angmar Awakened'
      WHEN p.date_release >= '2016-02-11' and p.date_release < '2016-11-23' THEN 'Dream-chaser'
      WHEN p.date_release >= '2016-11-23' and p.date_release < '2018-06-14' THEN 'Haradrim'
      WHEN p.date_release >= '2018-06-14' and p.date_release < '2019-08-02' THEN 'Ered Mithrin'
      WHEN p.date_release >= '2019-08-02' and p.date_release < '2021-03-21' THEN 'Vengeance of Mordor'
      ELSE 'ALeP - Oaths of the Rohirrim'
    END AS cycle,
    COUNT(*) AS number
  FROM (
    SELECT last_pack_id
    FROM decklist
    WHERE date_creation LIKE '" . $month . "-%'
    UNION ALL
    SELECT d.last_pack_id
    FROM deck d
    LEFT JOIN decklist dl
    ON d.id = dl.parent_deck_id
    WHERE dl.parent_deck_id IS NULL
      AND d.last_pack_id IS NOT NULL
      AND d.problem IS NULL
      AND (('" . $month . "' < '2021-04' AND d.date_update LIKE '" . $month . "-%') OR
           ('" . $month . "' >= '2021-04' AND '" . $month . "' < '2022-02' AND
            (d.date_creation LIKE '" . $month . "-%' OR (d.date_update LIKE '" . $month . "-%' AND d.date_creation < '2021-04-01'))) OR
           ('" . $month . "' >= '2022-02' AND d.date_creation LIKE '" . $month . "-%'))
  ) d
  JOIN pack p
  ON d.last_pack_id = p.id
  GROUP BY cycle
) d
ON c.cycle = d.cycle
LEFT JOIN (
  SELECT CASE
      WHEN date_release < '2011-07-21' THEN 'Core Set'
      WHEN date_release >= '2011-07-21' and date_release < '2012-01-06' THEN 'Shadows of Mirkwood'
      WHEN date_release >= '2012-01-06' and date_release < '2012-08-17' THEN 'Dwarrowdelf'
      WHEN date_release >= '2012-08-17' and date_release < '2014-02-21' THEN 'Against the Shadow'
      WHEN date_release >= '2014-02-21' and date_release < '2015-04-03' THEN 'The Ring-maker'
      WHEN date_release >= '2015-04-03' and date_release < '2016-02-11' THEN 'Angmar Awakened'
      WHEN date_release >= '2016-02-11' and date_release < '2016-11-23' THEN 'Dream-chaser'
      WHEN date_release >= '2016-11-23' and date_release < '2018-06-14' THEN 'Haradrim'
      WHEN date_release >= '2018-06-14' and date_release < '2019-08-02' THEN 'Ered Mithrin'
      WHEN date_release >= '2019-08-02' and date_release < '2021-03-21' THEN 'Vengeance of Mordor'
      ELSE 'ALeP - Oaths of the Rohirrim'
    END AS cycle,
    COUNT(*) AS number
  FROM (
    SELECT MAX(p.date_release) AS date_release,
      d.user_id
    FROM (
      SELECT last_pack_id,
        user_id
      FROM decklist
      WHERE date_creation LIKE '" . $month . "-%'
      UNION ALL
      SELECT d.last_pack_id,
        d.user_id
      FROM deck d
      LEFT JOIN decklist dl
      ON d.id = dl.parent_deck_id
      WHERE dl.parent_deck_id IS NULL
        AND d.last_pack_id IS NOT NULL
        AND d.problem IS NULL
        AND (('" . $month . "' < '2021-04' AND d.date_update LIKE '" . $month . "-%') OR
             ('" . $month . "' >= '2021-04' AND '" . $month . "' < '2022-02' AND
              (d.date_creation LIKE '" . $month . "-%' OR (d.date_update LIKE '" . $month . "-%' AND d.date_creation < '2021-04-01'))) OR
             ('" . $month . "' >= '2022-02' AND d.date_creation LIKE '" . $month . "-%'))
    ) d
    JOIN pack p
    ON d.last_pack_id = p.id
    GROUP BY d.user_id
  ) t
  GROUP BY cycle
) u
ON c.cycle = u.cycle";
		$res_decks_created = $dbh->executeQuery($query, [])->fetchAll(\PDO::FETCH_ASSOC);

		$query = "SELECT '" . $month . "' AS month,
  c.cycle,
  IFNULL(d.number, 0) AS number_decks,
  IFNULL(u.number, 0) AS number_users
FROM (
  SELECT 'Core Set' AS cycle
  UNION
  SELECT 'Shadows of Mirkwood' AS cycle
  UNION
  SELECT 'Dwarrowdelf' AS cycle
  UNION
  SELECT 'Against the Shadow' AS cycle
  UNION
  SELECT 'The Ring-maker' AS cycle
  UNION
  SELECT 'Angmar Awakened' AS cycle
  UNION
  SELECT 'Dream-chaser' AS cycle
  UNION
  SELECT 'Haradrim' AS cycle
  UNION
  SELECT 'Ered Mithrin' AS cycle
  UNION
  SELECT 'Vengeance of Mordor' AS cycle
  UNION
  SELECT 'ALeP - Oaths of the Rohirrim' AS cycle
) c
LEFT JOIN (
  SELECT CASE
      WHEN p.date_release < '2011-07-21' THEN 'Core Set'
      WHEN p.date_release >= '2011-07-21' and p.date_release < '2012-01-06' THEN 'Shadows of Mirkwood'
      WHEN p.date_release >= '2012-01-06' and p.date_release < '2012-08-17' THEN 'Dwarrowdelf'
      WHEN p.date_release >= '2012-08-17' and p.date_release < '2014-02-21' THEN 'Against the Shadow'
      WHEN p.date_release >= '2014-02-21' and p.date_release < '2015-04-03' THEN 'The Ring-maker'
      WHEN p.date_release >= '2015-04-03' and p.date_release < '2016-02-11' THEN 'Angmar Awakened'
      WHEN p.date_release >= '2016-02-11' and p.date_release < '2016-11-23' THEN 'Dream-chaser'
      WHEN p.date_release >= '2016-11-23' and p.date_release < '2018-06-14' THEN 'Haradrim'
      WHEN p.date_release >= '2018-06-14' and p.date_release < '2019-08-02' THEN 'Ered Mithrin'
      WHEN p.date_release >= '2019-08-02' and p.date_release < '2021-03-21' THEN 'Vengeance of Mordor'
      ELSE 'ALeP - Oaths of the Rohirrim'
    END AS cycle,
    COUNT(*) AS number
  FROM (
    SELECT dl.last_pack_id
    FROM questlog q
    JOIN questlog_deck qd
    ON q.id = qd.questlog_id
    JOIN decklist dl
    ON qd.decklist_id = dl.id
    WHERE q.date_played LIKE '" . $month . "-%'
    UNION ALL
    SELECT d.last_pack_id
    FROM questlog q
    JOIN questlog_deck qd
    ON q.id = qd.questlog_id
    JOIN deck d
    ON qd.deck_id = d.id
    WHERE qd.decklist_id IS NULL
      AND q.date_played LIKE '" . $month . "-%'
  ) d
  JOIN pack p
  ON d.last_pack_id = p.id
  GROUP BY cycle
) d
ON c.cycle = d.cycle
LEFT JOIN (
  SELECT CASE
      WHEN date_release < '2011-07-21' THEN 'Core Set'
      WHEN date_release >= '2011-07-21' and date_release < '2012-01-06' THEN 'Shadows of Mirkwood'
      WHEN date_release >= '2012-01-06' and date_release < '2012-08-17' THEN 'Dwarrowdelf'
      WHEN date_release >= '2012-08-17' and date_release < '2014-02-21' THEN 'Against the Shadow'
      WHEN date_release >= '2014-02-21' and date_release < '2015-04-03' THEN 'The Ring-maker'
      WHEN date_release >= '2015-04-03' and date_release < '2016-02-11' THEN 'Angmar Awakened'
      WHEN date_release >= '2016-02-11' and date_release < '2016-11-23' THEN 'Dream-chaser'
      WHEN date_release >= '2016-11-23' and date_release < '2018-06-14' THEN 'Haradrim'
      WHEN date_release >= '2018-06-14' and date_release < '2019-08-02' THEN 'Ered Mithrin'
      WHEN date_release >= '2019-08-02' and date_release < '2021-03-21' THEN 'Vengeance of Mordor'
      ELSE 'ALeP - Oaths of the Rohirrim'
    END AS cycle,
    COUNT(*) AS number
  FROM (
    SELECT MAX(p.date_release) AS date_release,
      d.user_id
    FROM (
      SELECT dl.last_pack_id,
        q.user_id
      FROM questlog q
      JOIN questlog_deck qd
      ON q.id = qd.questlog_id
      JOIN decklist dl
      ON qd.decklist_id = dl.id
      WHERE q.date_played LIKE '" . $month . "-%'
      UNION ALL
      SELECT d.last_pack_id,
        q.user_id
      FROM questlog q
      JOIN questlog_deck qd
      ON q.id = qd.questlog_id
      JOIN deck d
      ON qd.deck_id = d.id
      WHERE qd.decklist_id IS NULL
        AND q.date_played LIKE '" . $month . "-%'
    ) d
    JOIN pack p
    ON d.last_pack_id = p.id
    GROUP BY d.user_id
  ) t
  GROUP BY cycle
) u
ON c.cycle = u.cycle";
		$res_decks_played = $dbh->executeQuery($query, [])->fetchAll(\PDO::FETCH_ASSOC);

		$query = "SELECT '" . $month . "' AS month,
  c.cycle,
  IFNULL(d.number, 0) AS number_quests,
  IFNULL(u.number, 0) AS number_users
FROM (
  SELECT 'Core Set' AS cycle
  UNION
  SELECT 'Shadows of Mirkwood' AS cycle
  UNION
  SELECT 'Dwarrowdelf' AS cycle
  UNION
  SELECT 'Against the Shadow' AS cycle
  UNION
  SELECT 'The Ring-maker' AS cycle
  UNION
  SELECT 'Angmar Awakened' AS cycle
  UNION
  SELECT 'Dream-chaser' AS cycle
  UNION
  SELECT 'Haradrim' AS cycle
  UNION
  SELECT 'Ered Mithrin' AS cycle
  UNION
  SELECT 'Vengeance of Mordor' AS cycle
  UNION
  SELECT 'ALeP - Oaths of the Rohirrim' AS cycle
) c
LEFT JOIN (
  SELECT CASE
      WHEN p.date_release < '2011-07-21' THEN 'Core Set'
      WHEN p.date_release >= '2011-07-21' and p.date_release < '2012-01-06' THEN 'Shadows of Mirkwood'
      WHEN p.date_release >= '2012-01-06' and p.date_release < '2012-08-17' THEN 'Dwarrowdelf'
      WHEN p.date_release >= '2012-08-17' and p.date_release < '2014-02-21' THEN 'Against the Shadow'
      WHEN p.date_release >= '2014-02-21' and p.date_release < '2015-04-03' THEN 'The Ring-maker'
      WHEN p.date_release >= '2015-04-03' and p.date_release < '2016-02-11' THEN 'Angmar Awakened'
      WHEN p.date_release >= '2016-02-11' and p.date_release < '2016-11-23' THEN 'Dream-chaser'
      WHEN p.date_release >= '2016-11-23' and p.date_release < '2018-06-14' THEN 'Haradrim'
      WHEN p.date_release >= '2018-06-14' and p.date_release < '2019-08-02' THEN 'Ered Mithrin'
      WHEN p.date_release >= '2019-08-02' and p.date_release < '2021-03-21' THEN 'Vengeance of Mordor'
      ELSE 'ALeP - Oaths of the Rohirrim'
    END AS cycle,
    COUNT(*) AS number
  FROM (
    SELECT s.pack_id AS last_pack_id
    FROM questlog q
    JOIN scenario s
    ON q.scenario_id = s.id
    WHERE q.date_played LIKE '" . $month . "-%'
  ) d
  JOIN pack p
  ON d.last_pack_id = p.id
  GROUP BY cycle
) d
ON c.cycle = d.cycle
LEFT JOIN (
  SELECT CASE
      WHEN date_release < '2011-07-21' THEN 'Core Set'
      WHEN date_release >= '2011-07-21' and date_release < '2012-01-06' THEN 'Shadows of Mirkwood'
      WHEN date_release >= '2012-01-06' and date_release < '2012-08-17' THEN 'Dwarrowdelf'
      WHEN date_release >= '2012-08-17' and date_release < '2014-02-21' THEN 'Against the Shadow'
      WHEN date_release >= '2014-02-21' and date_release < '2015-04-03' THEN 'The Ring-maker'
      WHEN date_release >= '2015-04-03' and date_release < '2016-02-11' THEN 'Angmar Awakened'
      WHEN date_release >= '2016-02-11' and date_release < '2016-11-23' THEN 'Dream-chaser'
      WHEN date_release >= '2016-11-23' and date_release < '2018-06-14' THEN 'Haradrim'
      WHEN date_release >= '2018-06-14' and date_release < '2019-08-02' THEN 'Ered Mithrin'
      WHEN date_release >= '2019-08-02' and date_release < '2021-03-21' THEN 'Vengeance of Mordor'
      ELSE 'ALeP - Oaths of the Rohirrim'
    END AS cycle,
    COUNT(*) AS number
  FROM (
    SELECT MAX(p.date_release) AS date_release,
      d.user_id
    FROM (
      SELECT s.pack_id AS last_pack_id,
        q.user_id
      FROM questlog q
      JOIN scenario s
      ON q.scenario_id = s.id
      WHERE q.date_played LIKE '" . $month . "-%'
    ) d
    JOIN pack p
    ON d.last_pack_id = p.id
    GROUP BY d.user_id
  ) t
  GROUP BY cycle
) u
ON c.cycle = u.cycle";
		$res_quests_played = $dbh->executeQuery($query, [])->fetchAll(\PDO::FETCH_ASSOC);

		$res = ['decks_created' => $res_decks_created,
				'decks_played' => $res_decks_played,
				'quests_played' => $res_quests_played,
				'packs' => $packs,
				'pack_rules' => $pack_rules];
		$response = new Response(json_encode($res));
		$response->headers->set('Content-Type', 'application/json');
		return $response;
	}

	public function getStatCardsAction(Request $request) {
		$month = $request->query->get('month');
		if (!$month) {
			$month = date('Y-m', strtotime('first day of last month'));
		}

		$step = $request->query->get('step');
		if (!$step) {
			$step = '1';
		}

        /* @var $dbh \Doctrine\DBAL\Connection */
        $dbh = $this->getDoctrine()->getConnection();

		if ($step == '1') {
			$query1 = "SELECT c.code,
  c.octgnid,
  c.name,
  t.name AS type,
  s.name AS sphere,
  p.name AS pack,
  p.date_release AS released,
  CASE WHEN c.cost = '-' THEN 'Encounter' ELSE '' END AS encounter,
  COUNT(sl.new_code) AS full_decks,
  ROUND(COALESCE(AVG(sl.quantity), 0), 2) AS full_deck_copies
FROM card c
JOIN pack p
ON c.pack_id = p.id
JOIN type t
ON c.type_id = t.id
JOIN sphere s
ON c.sphere_id = s.id
LEFT JOIN (
  SELECT CASE WHEN CAST(c.code AS UNSIGNED) > 1000000 THEN SUBSTRING(c.code, 3) ELSE source_code(c.code, cp.name) END AS new_code,
    LEAST(SUM(dls.quantity), 3) AS quantity
  FROM decklist dl
  JOIN pack p
  ON dl.last_pack_id = p.id
  JOIN decklistslot dls
  ON dl.id = dls.decklist_id
  JOIN card c
  ON dls.card_id = c.id
  JOIN pack cp
  ON c.pack_id = cp.id
  WHERE dl.date_creation LIKE '" . $month . "-%'
    AND p.date_release >= '2019-08-02'
    AND (c.cost IS NULL OR c.cost != '-')
  GROUP BY dl.id, new_code

  UNION ALL

  SELECT '22134' AS new_code,
    1 AS quantity
  FROM (
    SELECT DISTINCT dl.id
    FROM decklist dl
    JOIN pack p
    ON dl.last_pack_id = p.id
    JOIN decklistslot dls
    ON dl.id = dls.decklist_id
    JOIN card c
    ON dls.card_id = c.id
    WHERE dl.date_creation LIKE '" . $month . "-%'
      AND p.date_release >= '2019-08-02'
      AND CAST(c.code AS UNSIGNED) > 1000000
  ) t1
  LEFT JOIN (
    SELECT DISTINCT dl.id
    FROM decklist dl
    JOIN pack p
    ON dl.last_pack_id = p.id
    JOIN decklistslot dls
    ON dl.id = dls.decklist_id
    JOIN card c
    ON dls.card_id = c.id
    WHERE dl.date_creation LIKE '" . $month . "-%'
      AND p.date_release >= '2019-08-02'
      AND c.code = '22134'
  ) t2
  ON t1.id = t2.id
  WHERE t2.id IS NULL

  UNION ALL

  SELECT CASE WHEN CAST(c.code AS UNSIGNED) > 1000000 THEN SUBSTRING(c.code, 3) ELSE source_code(c.code, cp.name) END AS new_code,
    LEAST(SUM(ds.quantity), 3) AS quantity
  FROM deck d
  LEFT JOIN decklist dl
  ON d.id = dl.parent_deck_id
  JOIN pack p
  ON d.last_pack_id = p.id
  JOIN deckslot ds
  ON d.id = ds.deck_id
  JOIN card c
  ON ds.card_id = c.id
  JOIN pack cp
  ON c.pack_id = cp.id
  WHERE dl.parent_deck_id IS NULL
    AND d.last_pack_id IS NOT NULL
    AND d.problem IS NULL
    AND (('" . $month . "' < '2022-08' AND d.date_update LIKE '" . $month . "-%') OR
         ('" . $month . "' >= '2022-08' AND d.date_creation LIKE '" . $month . "-%'))
    AND p.date_release >= '2019-08-02'
    AND (c.cost IS NULL OR c.cost != '-')
  GROUP BY d.id, new_code

  UNION ALL

  SELECT '22134' AS new_code,
    1 AS quantity
  FROM (
    SELECT DISTINCT d.id
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
      AND (('" . $month . "' < '2022-08' AND d.date_update LIKE '" . $month . "-%') OR
           ('" . $month . "' >= '2022-08' AND d.date_creation LIKE '" . $month . "-%'))
      AND p.date_release >= '2019-08-02'
      AND CAST(c.code AS UNSIGNED) > 1000000
  ) t1
  LEFT JOIN (
    SELECT DISTINCT d.id
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
      AND (('" . $month . "' < '2022-08' AND d.date_update LIKE '" . $month . "-%') OR
           ('" . $month . "' >= '2022-08' AND d.date_creation LIKE '" . $month . "-%'))
      AND p.date_release >= '2019-08-02'
      AND c.code = '22134'
  ) t2
  ON t1.id = t2.id
  WHERE t2.id IS NULL
) sl
ON c.code = sl.new_code
WHERE t.name != 'Campaign'
  AND s.name NOT IN ('Baggins', 'Fellowship')
  AND p.name NOT IN ('Messenger of the King Allies', 'ALeP - Messenger of the King Allies', 'Two-Player Limited Edition Starter',
                     'Dwarves of Durin', 'Elves of Lórien', 'Defenders of Gondor', 'Riders of Rohan')
  AND CAST(p.date_release AS CHAR) <= '" . $month . "-31'
GROUP BY c.code
ORDER BY encounter, full_decks DESC, CAST(c.code AS UNSIGNED) DESC";
			$cards = $dbh->executeQuery($query1, [])->fetchAll(\PDO::FETCH_ASSOC);

			$res = ['cards' => $cards];
			$response = new Response(json_encode($res));
			$response->headers->set('Content-Type', 'application/json');
			return $response;
		}

		if ($step == '2') {
			$query2 = "SELECT c.code,
    COUNT(sl.new_code) AS limited_decks,
    ROUND(COALESCE(AVG(sl.quantity), 0), 2) AS limited_deck_copies
  FROM card c
  JOIN pack p
  ON c.pack_id = p.id
  JOIN type t
  ON c.type_id = t.id
  JOIN sphere s
  ON c.sphere_id = s.id
  LEFT JOIN (
    SELECT CASE WHEN CAST(c.code AS UNSIGNED) > 1000000 THEN SUBSTRING(c.code, 3) ELSE source_code(c.code, cp.name) END AS new_code,
      LEAST(SUM(dls.quantity), 3) AS quantity
    FROM decklist dl
    JOIN pack p
    ON dl.last_pack_id = p.id
    JOIN decklistslot dls
    ON dl.id = dls.decklist_id
    JOIN card c
    ON dls.card_id = c.id
    JOIN pack cp
    ON c.pack_id = cp.id
    WHERE dl.date_creation LIKE '" . $month . "-%'
      AND p.date_release < '2019-08-02'
      AND (c.cost IS NULL OR c.cost != '-')
    GROUP BY dl.id, new_code

    UNION ALL

    SELECT '22134' AS new_code,
      1 AS quantity
    FROM (
      SELECT DISTINCT dl.id
      FROM decklist dl
      JOIN pack p
      ON dl.last_pack_id = p.id
      JOIN decklistslot dls
      ON dl.id = dls.decklist_id
      JOIN card c
      ON dls.card_id = c.id
      WHERE dl.date_creation LIKE '" . $month . "-%'
        AND p.date_release < '2019-08-02'
        AND CAST(c.code AS UNSIGNED) > 1000000
    ) t1
    LEFT JOIN (
      SELECT DISTINCT dl.id
      FROM decklist dl
      JOIN pack p
      ON dl.last_pack_id = p.id
      JOIN decklistslot dls
      ON dl.id = dls.decklist_id
      JOIN card c
      ON dls.card_id = c.id
      WHERE dl.date_creation LIKE '" . $month . "-%'
        AND p.date_release < '2019-08-02'
        AND c.code = '22134'
    ) t2
    ON t1.id = t2.id
    WHERE t2.id IS NULL

    UNION ALL

    SELECT CASE WHEN CAST(c.code AS UNSIGNED) > 1000000 THEN SUBSTRING(c.code, 3) ELSE source_code(c.code, cp.name) END AS new_code,
      LEAST(SUM(ds.quantity), 3) AS quantity
    FROM deck d
    LEFT JOIN decklist dl
    ON d.id = dl.parent_deck_id
    JOIN pack p
    ON d.last_pack_id = p.id
    JOIN deckslot ds
    ON d.id = ds.deck_id
    JOIN card c
    ON ds.card_id = c.id
    JOIN pack cp
    ON c.pack_id = cp.id
    WHERE dl.parent_deck_id IS NULL
      AND d.last_pack_id IS NOT NULL
      AND d.problem IS NULL
      AND (('" . $month . "' < '2022-08' AND d.date_update LIKE '" . $month . "-%') OR
           ('" . $month . "' >= '2022-08' AND d.date_creation LIKE '" . $month . "-%'))
      AND p.date_release < '2019-08-02'
      AND (c.cost IS NULL OR c.cost != '-')
    GROUP BY d.id, new_code

    UNION ALL

    SELECT '22134' AS new_code,
      1 AS quantity
    FROM (
      SELECT DISTINCT d.id
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
        AND (('" . $month . "' < '2022-08' AND d.date_update LIKE '" . $month . "-%') OR
             ('" . $month . "' >= '2022-08' AND d.date_creation LIKE '" . $month . "-%'))
        AND p.date_release < '2019-08-02'
        AND CAST(c.code AS UNSIGNED) > 1000000
    ) t1
    LEFT JOIN (
      SELECT DISTINCT d.id
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
        AND (('" . $month . "' < '2022-08' AND d.date_update LIKE '" . $month . "-%') OR
             ('" . $month . "' >= '2022-08' AND d.date_creation LIKE '" . $month . "-%'))
        AND p.date_release < '2019-08-02'
        AND c.code = '22134'
    ) t2
    ON t1.id = t2.id
    WHERE t2.id IS NULL
  ) sl
  ON c.code = sl.new_code
  WHERE t.name != 'Campaign'
    AND s.name NOT IN ('Baggins', 'Fellowship')
    AND p.name NOT IN ('Messenger of the King Allies', 'ALeP - Messenger of the King Allies', 'Two-Player Limited Edition Starter',
                       'Dwarves of Durin', 'Elves of Lórien', 'Defenders of Gondor', 'Riders of Rohan')
    AND CAST(p.date_release AS CHAR) <= '" . $month . "-31'
  GROUP BY c.code";
			$cards = $dbh->executeQuery($query2, [])->fetchAll(\PDO::FETCH_ASSOC);

			$res = ['cards' => $cards];
			$response = new Response(json_encode($res));
			$response->headers->set('Content-Type', 'application/json');
			return $response;
		}

		if ($step == '3') {
			$query3 = "SELECT c.code,
    COUNT(sl.new_code) AS sides,
    ROUND(COALESCE(AVG(sl.quantity), 0), 2) AS side_copies
  FROM card c
  JOIN pack p
  ON c.pack_id = p.id
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
    JOIN pack cp
    ON c.pack_id = cp.id
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
    JOIN pack cp
    ON c.pack_id = cp.id
    WHERE dl.parent_deck_id IS NULL
      AND d.last_pack_id IS NOT NULL
      AND d.problem IS NULL
      AND (('" . $month . "' < '2022-08' AND d.date_update LIKE '" . $month . "-%') OR
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
      AND (('" . $month . "' < '2022-08' AND d.date_update LIKE '" . $month . "-%') OR
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
  GROUP BY c.code";
			$cards = $dbh->executeQuery($query3, [])->fetchAll(\PDO::FETCH_ASSOC);

			$query_total = "SELECT full_decks,
  limited_decks,
  full_decks + limited_decks AS sides
FROM (
  SELECT (SELECT COUNT(*)
    FROM decklist dl
    JOIN pack p
    ON dl.last_pack_id = p.id
    WHERE dl.date_creation LIKE '" . $month . "-%' -- month
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
      AND (('" . $month . "' < '2022-08' AND d.date_update LIKE '" . $month . "-%') OR -- month
           ('" . $month . "' >= '2022-08' AND d.date_creation LIKE '" . $month . "-%')) -- month
      AND p.date_release >= '2019-08-02'
    ) AS full_decks,
    (SELECT COUNT(*)
    FROM decklist dl
    JOIN pack p
    ON dl.last_pack_id = p.id
    WHERE dl.date_creation LIKE '" . $month . "-%' -- month
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
      AND (('" . $month . "' < '2022-08' AND d.date_update LIKE '" . $month . "-%') OR -- month
           ('" . $month . "' >= '2022-08' AND d.date_creation LIKE '" . $month . "-%')) -- month
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
			$response = new Response(json_encode($res));
			$response->headers->set('Content-Type', 'application/json');
			return $response;
		}
/*
		$query = "SELECT c.octgnid,
  c.name,
  t.name AS type,
  s.name AS sphere,
  p.name AS pack,
  p.date_release AS released,
  CASE WHEN c.cost = '-' THEN 'Encounter' ELSE '' END AS encounter,
  COUNT(sl.new_code) AS full_decks,
  ROUND(COALESCE(AVG(sl.quantity), 0), 2) AS full_deck_copies,
  ld.limited_decks,
  ld.limited_deck_copies,
  sd.sides,
  sd.side_copies
FROM card c
JOIN pack p
ON c.pack_id = p.id
JOIN type t
ON c.type_id = t.id
JOIN sphere s
ON c.sphere_id = s.id
LEFT JOIN (
  SELECT CASE WHEN CAST(c.code AS UNSIGNED) > 1000000 THEN SUBSTRING(c.code, 3) ELSE source_code(c.code, cp.name) END AS new_code,
    LEAST(SUM(dls.quantity), 3) AS quantity
  FROM decklist dl
  JOIN pack p
  ON dl.last_pack_id = p.id
  JOIN decklistslot dls
  ON dl.id = dls.decklist_id
  JOIN card c
  ON dls.card_id = c.id
  JOIN pack cp
  ON c.pack_id = cp.id
  WHERE dl.date_creation LIKE '" . $month . "-%'
    AND p.date_release >= '2019-08-02'
    AND (c.cost IS NULL OR c.cost != '-')
  GROUP BY dl.id, new_code

  UNION ALL

  SELECT '22134' AS new_code,
    1 AS quantity
  FROM (
    SELECT DISTINCT dl.id
    FROM decklist dl
    JOIN pack p
    ON dl.last_pack_id = p.id
    JOIN decklistslot dls
    ON dl.id = dls.decklist_id
    JOIN card c
    ON dls.card_id = c.id
    WHERE dl.date_creation LIKE '" . $month . "-%'
      AND p.date_release >= '2019-08-02'
      AND CAST(c.code AS UNSIGNED) > 1000000
  ) t1
  LEFT JOIN (
    SELECT DISTINCT dl.id
    FROM decklist dl
    JOIN pack p
    ON dl.last_pack_id = p.id
    JOIN decklistslot dls
    ON dl.id = dls.decklist_id
    JOIN card c
    ON dls.card_id = c.id
    WHERE dl.date_creation LIKE '" . $month . "-%'
      AND p.date_release >= '2019-08-02'
      AND c.code = '22134'
  ) t2
  ON t1.id = t2.id
  WHERE t2.id IS NULL

  UNION ALL

  SELECT CASE WHEN CAST(c.code AS UNSIGNED) > 1000000 THEN SUBSTRING(c.code, 3) ELSE source_code(c.code, cp.name) END AS new_code,
    LEAST(SUM(ds.quantity), 3) AS quantity
  FROM deck d
  LEFT JOIN decklist dl
  ON d.id = dl.parent_deck_id
  JOIN pack p
  ON d.last_pack_id = p.id
  JOIN deckslot ds
  ON d.id = ds.deck_id
  JOIN card c
  ON ds.card_id = c.id
  JOIN pack cp
  ON c.pack_id = cp.id
  WHERE dl.parent_deck_id IS NULL
    AND d.last_pack_id IS NOT NULL
    AND d.problem IS NULL
    AND (('" . $month . "' < '2022-08' AND d.date_update LIKE '" . $month . "-%') OR
         ('" . $month . "' >= '2022-08' AND d.date_creation LIKE '" . $month . "-%'))
    AND p.date_release >= '2019-08-02'
    AND (c.cost IS NULL OR c.cost != '-')
  GROUP BY d.id, new_code

  UNION ALL

  SELECT '22134' AS new_code,
    1 AS quantity
  FROM (
    SELECT DISTINCT d.id
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
      AND (('" . $month . "' < '2022-08' AND d.date_update LIKE '" . $month . "-%') OR
           ('" . $month . "' >= '2022-08' AND d.date_creation LIKE '" . $month . "-%'))
      AND p.date_release >= '2019-08-02'
      AND CAST(c.code AS UNSIGNED) > 1000000
  ) t1
  LEFT JOIN (
    SELECT DISTINCT d.id
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
      AND (('" . $month . "' < '2022-08' AND d.date_update LIKE '" . $month . "-%') OR
           ('" . $month . "' >= '2022-08' AND d.date_creation LIKE '" . $month . "-%'))
      AND p.date_release >= '2019-08-02'
      AND c.code = '22134'
  ) t2
  ON t1.id = t2.id
  WHERE t2.id IS NULL
) sl
ON c.code = sl.new_code
JOIN (
  SELECT c.code,
    COUNT(sl.new_code) AS limited_decks,
    ROUND(COALESCE(AVG(sl.quantity), 0), 2) AS limited_deck_copies
  FROM card c
  JOIN pack p
  ON c.pack_id = p.id
  JOIN type t
  ON c.type_id = t.id
  JOIN sphere s
  ON c.sphere_id = s.id
  LEFT JOIN (
    SELECT CASE WHEN CAST(c.code AS UNSIGNED) > 1000000 THEN SUBSTRING(c.code, 3) ELSE source_code(c.code, cp.name) END AS new_code,
      LEAST(SUM(dls.quantity), 3) AS quantity
    FROM decklist dl
    JOIN pack p
    ON dl.last_pack_id = p.id
    JOIN decklistslot dls
    ON dl.id = dls.decklist_id
    JOIN card c
    ON dls.card_id = c.id
    JOIN pack cp
    ON c.pack_id = cp.id
    WHERE dl.date_creation LIKE '" . $month . "-%'
      AND p.date_release < '2019-08-02'
      AND (c.cost IS NULL OR c.cost != '-')
    GROUP BY dl.id, new_code

    UNION ALL

    SELECT '22134' AS new_code,
      1 AS quantity
    FROM (
      SELECT DISTINCT dl.id
      FROM decklist dl
      JOIN pack p
      ON dl.last_pack_id = p.id
      JOIN decklistslot dls
      ON dl.id = dls.decklist_id
      JOIN card c
      ON dls.card_id = c.id
      WHERE dl.date_creation LIKE '" . $month . "-%'
        AND p.date_release < '2019-08-02'
        AND CAST(c.code AS UNSIGNED) > 1000000
    ) t1
    LEFT JOIN (
      SELECT DISTINCT dl.id
      FROM decklist dl
      JOIN pack p
      ON dl.last_pack_id = p.id
      JOIN decklistslot dls
      ON dl.id = dls.decklist_id
      JOIN card c
      ON dls.card_id = c.id
      WHERE dl.date_creation LIKE '" . $month . "-%'
        AND p.date_release < '2019-08-02'
        AND c.code = '22134'
    ) t2
    ON t1.id = t2.id
    WHERE t2.id IS NULL

    UNION ALL

    SELECT CASE WHEN CAST(c.code AS UNSIGNED) > 1000000 THEN SUBSTRING(c.code, 3) ELSE source_code(c.code, cp.name) END AS new_code,
      LEAST(SUM(ds.quantity), 3) AS quantity
    FROM deck d
    LEFT JOIN decklist dl
    ON d.id = dl.parent_deck_id
    JOIN pack p
    ON d.last_pack_id = p.id
    JOIN deckslot ds
    ON d.id = ds.deck_id
    JOIN card c
    ON ds.card_id = c.id
    JOIN pack cp
    ON c.pack_id = cp.id
    WHERE dl.parent_deck_id IS NULL
      AND d.last_pack_id IS NOT NULL
      AND d.problem IS NULL
      AND (('" . $month . "' < '2022-08' AND d.date_update LIKE '" . $month . "-%') OR
           ('" . $month . "' >= '2022-08' AND d.date_creation LIKE '" . $month . "-%'))
      AND p.date_release < '2019-08-02'
      AND (c.cost IS NULL OR c.cost != '-')
    GROUP BY d.id, new_code

    UNION ALL

    SELECT '22134' AS new_code,
      1 AS quantity
    FROM (
      SELECT DISTINCT d.id
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
        AND (('" . $month . "' < '2022-08' AND d.date_update LIKE '" . $month . "-%') OR
             ('" . $month . "' >= '2022-08' AND d.date_creation LIKE '" . $month . "-%'))
        AND p.date_release < '2019-08-02'
        AND CAST(c.code AS UNSIGNED) > 1000000
    ) t1
    LEFT JOIN (
      SELECT DISTINCT d.id
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
        AND (('" . $month . "' < '2022-08' AND d.date_update LIKE '" . $month . "-%') OR
             ('" . $month . "' >= '2022-08' AND d.date_creation LIKE '" . $month . "-%'))
        AND p.date_release < '2019-08-02'
        AND c.code = '22134'
    ) t2
    ON t1.id = t2.id
    WHERE t2.id IS NULL
  ) sl
  ON c.code = sl.new_code
  WHERE t.name != 'Campaign'
    AND s.name NOT IN ('Baggins', 'Fellowship')
    AND p.name NOT IN ('Messenger of the King Allies', 'ALeP - Messenger of the King Allies', 'Two-Player Limited Edition Starter',
                       'Dwarves of Durin', 'Elves of Lórien', 'Defenders of Gondor', 'Riders of Rohan')
    AND CAST(p.date_release AS CHAR) <= '" . $month . "-31'
  GROUP BY c.code
) ld
ON c.code = ld.code
JOIN (
  SELECT c.code,
    COUNT(sl.new_code) AS sides,
    ROUND(COALESCE(AVG(sl.quantity), 0), 2) AS side_copies
  FROM card c
  JOIN pack p
  ON c.pack_id = p.id
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
    JOIN pack cp
    ON c.pack_id = cp.id
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
    JOIN pack cp
    ON c.pack_id = cp.id
    WHERE dl.parent_deck_id IS NULL
      AND d.last_pack_id IS NOT NULL
      AND d.problem IS NULL
      AND (('" . $month . "' < '2022-08' AND d.date_update LIKE '" . $month . "-%') OR
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
      AND (('" . $month . "' < '2022-08' AND d.date_update LIKE '" . $month . "-%') OR
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
  GROUP BY c.code
) sd
ON c.code = sd.code
WHERE t.name != 'Campaign'
  AND s.name NOT IN ('Baggins', 'Fellowship')
  AND p.name NOT IN ('Messenger of the King Allies', 'ALeP - Messenger of the King Allies', 'Two-Player Limited Edition Starter',
                     'Dwarves of Durin', 'Elves of Lórien', 'Defenders of Gondor', 'Riders of Rohan')
  AND CAST(p.date_release AS CHAR) <= '" . $month . "-31'
GROUP BY c.code
ORDER BY encounter, full_decks DESC, limited_decks DESC, sides DESC, CAST(c.code AS UNSIGNED) DESC";
*/
	}

	public function getStatPacksAction(Request $request) {
        /* @var $dbh \Doctrine\DBAL\Connection */
		$packs = $this->getPacks();
		$pack_rules = $this->getPackRuless();
		$quests = $this->getQuests();

		$res = ['packs' => $packs,
				'pack_rules' => $pack_rules,
				'quests' => $quests];
		$response = new Response(json_encode($res));
		$response->headers->set('Content-Type', 'application/json');
		return $response;
	}

	function getPacks() {
		$dbh = $this->getDoctrine()->getConnection();

		$query = "SELECT name, date_release
FROM pack
WHERE date_release IS NOT NULL
ORDER BY date_release";
		$packs = $dbh->executeQuery($query, [])->fetchAll(\PDO::FETCH_ASSOC);
		return $packs;
	}

	function getPackRuless() {
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

	function getQuests() {
		$dbh = $this->getDoctrine()->getConnection();

		$query = "SELECT p.name, GROUP_CONCAT(s.name SEPARATOR ';') AS quests
FROM scenario s
JOIN pack p
ON s.pack_id = p.id
GROUP BY p.name
ORDER BY p.name";
		$quests = $dbh->executeQuery($query, [])->fetchAll(\PDO::FETCH_ASSOC);
		for ($i = 0; $i < count($quests); $i++) {
			$quests[$i]['quests'] = explode(';', $quests[$i]['quests']);
		}
		return $quests;
	}

	function getOctgnIdMapping() {
		$dbh = $this->getDoctrine()->getConnection();

		$query = "SELECT c1.octgnid AS id1,
  c2.octgnid AS id2
FROM card c1
JOIN pack p
ON c1.pack_id = p.id
JOIN card c2
ON source_code(c1.code, p.name) = c2.code
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