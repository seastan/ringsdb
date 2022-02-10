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

        /* @var $dbh \Doctrine\DBAL\Connection */
        $dbh = $this->getDoctrine()->getConnection();

		$query = "SELECT name, date_release
FROM pack
WHERE date_release IS NOT NULL
ORDER BY date_release";
		$packs = $dbh->executeQuery($query, [])->fetchAll(\PDO::FETCH_ASSOC);

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
      WHEN p.date_release >= '2012-01-06' and p.date_release < '2012-11-26' THEN 'Dwarrowdelf'
      WHEN p.date_release >= '2012-11-26' and p.date_release < '2014-02-21' THEN 'Against the Shadow'
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
      WHEN date_release >= '2012-01-06' and date_release < '2012-11-26' THEN 'Dwarrowdelf'
      WHEN date_release >= '2012-11-26' and date_release < '2014-02-21' THEN 'Against the Shadow'
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
      WHEN p.date_release >= '2012-01-06' and p.date_release < '2012-11-26' THEN 'Dwarrowdelf'
      WHEN p.date_release >= '2012-11-26' and p.date_release < '2014-02-21' THEN 'Against the Shadow'
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
      WHEN date_release >= '2012-01-06' and date_release < '2012-11-26' THEN 'Dwarrowdelf'
      WHEN date_release >= '2012-11-26' and date_release < '2014-02-21' THEN 'Against the Shadow'
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
      WHEN p.date_release >= '2012-01-06' and p.date_release < '2012-11-26' THEN 'Dwarrowdelf'
      WHEN p.date_release >= '2012-11-26' and p.date_release < '2014-02-21' THEN 'Against the Shadow'
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
      WHEN date_release >= '2012-01-06' and date_release < '2012-11-26' THEN 'Dwarrowdelf'
      WHEN date_release >= '2012-11-26' and date_release < '2014-02-21' THEN 'Against the Shadow'
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

		$pack_rules = ['Core Set' => ['2000-01-01', '2011-07-21'],
						'Shadows of Mirkwood' => ['2011-07-21', '2012-01-06'],
						'Dwarrowdelf' => ['2012-01-06', '2012-11-26'],
						'Against the Shadow' => ['2012-11-26', '2014-02-21'],
						'The Ring-maker' => ['2014-02-21', '2015-04-03'],
						'Angmar Awakened' => ['2015-04-03', '2016-02-11'],
						'Dream-chaser' => ['2016-02-11', '2016-11-23'],
						'Haradrim' => ['2016-11-23', '2018-06-14'],
						'Ered Mithrin' => ['2018-06-14', '2019-08-02'],
						'Vengeance of Mordor' => ['2019-08-02', '2021-03-21'],
						'ALeP - Oaths of the Rohirrim' => ['2021-03-21', '2099-12-31']];

		$res = ['decks_created' => $res_decks_created,
				'decks_played' => $res_decks_played,
				'quests_played' => $res_quests_played,
				'packs' => $packs,
				'pack_rules' => $pack_rules];
		$response = new Response(json_encode($res));
		$response->headers->set('Content-Type', 'application/json');
		return $response;
	}
}