<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class StatController extends AbstractController
{
    /**
     * @return Response
     *
     * @Route("/admin/stat", name="app_stat", methods={"GET"})
     */
    public function getStatAction(Request $request)
    {
        $month = $request->query->get('month');
        if (!$month) {
            $month = date('Y-m', strtotime('first day of last month'));
        }
        $packs = $this->getPacks();
        $pack_rules = $this->getPackRuless();
        /* @var $dbh \Doctrine\DBAL\Connection */
        $dbh = $this->getDoctrine()->getConnection();
        $query = "SELECT '".$month."' AS month,\n  c.cycle,\n  IFNULL(d.number, 0) AS number_decks,\n  IFNULL(u.number, 0) AS number_users\nFROM (\n  SELECT 'Core Set' AS cycle\n  UNION\n  SELECT 'Shadows of Mirkwood' AS cycle\n  UNION\n  SELECT 'Dwarrowdelf' AS cycle\n  UNION\n  SELECT 'Against the Shadow' AS cycle\n  UNION\n  SELECT 'The Ring-maker' AS cycle\n  UNION\n  SELECT 'Angmar Awakened' AS cycle\n  UNION\n  SELECT 'Dream-chaser' AS cycle\n  UNION\n  SELECT 'Haradrim' AS cycle\n  UNION\n  SELECT 'Ered Mithrin' AS cycle\n  UNION\n  SELECT 'Vengeance of Mordor' AS cycle\n  UNION\n  SELECT 'ALeP - Oaths of the Rohirrim' AS cycle\n) c\nLEFT JOIN (\n  SELECT CASE\n      WHEN p.date_release < '2011-07-21' THEN 'Core Set'\n      WHEN p.date_release >= '2011-07-21' and p.date_release < '2012-01-06' THEN 'Shadows of Mirkwood'\n      WHEN p.date_release >= '2012-01-06' and p.date_release < '2012-08-17' THEN 'Dwarrowdelf'\n      WHEN p.date_release >= '2012-08-17' and p.date_release < '2014-02-21' THEN 'Against the Shadow'\n      WHEN p.date_release >= '2014-02-21' and p.date_release < '2015-04-03' THEN 'The Ring-maker'\n      WHEN p.date_release >= '2015-04-03' and p.date_release < '2016-02-11' THEN 'Angmar Awakened'\n      WHEN p.date_release >= '2016-02-11' and p.date_release < '2016-11-23' THEN 'Dream-chaser'\n      WHEN p.date_release >= '2016-11-23' and p.date_release < '2018-06-14' THEN 'Haradrim'\n      WHEN p.date_release >= '2018-06-14' and p.date_release < '2019-08-02' THEN 'Ered Mithrin'\n      WHEN p.date_release >= '2019-08-02' and p.date_release < '2021-03-21' THEN 'Vengeance of Mordor'\n      ELSE 'ALeP - Oaths of the Rohirrim'\n    END AS cycle,\n    COUNT(*) AS number\n  FROM (\n    SELECT last_pack_id\n    FROM decklist\n    WHERE date_creation LIKE '".$month."-%'\n    UNION ALL\n    SELECT d.last_pack_id\n    FROM deck d\n    LEFT JOIN decklist dl\n    ON d.id = dl.parent_deck_id\n    WHERE dl.parent_deck_id IS NULL\n      AND d.last_pack_id IS NOT NULL\n      AND d.problem IS NULL\n      AND (('".$month."' < '2021-04' AND (d.date_update LIKE '".$month."-%' OR (d.date_creation LIKE '".$month."-%' AND d.date_update >= '2022-02-01'))) OR\n           ('".$month."' >= '2021-04' AND '".$month."' < '2022-02' AND\n            (d.date_creation LIKE '".$month."-%' OR (d.date_update LIKE '".$month."-%' AND d.date_creation < '2021-04-01'))) OR\n           ('".$month."' >= '2022-02' AND d.date_creation LIKE '".$month."-%'))\n  ) d\n  JOIN pack p\n  ON d.last_pack_id = p.id\n  GROUP BY cycle\n) d\nON c.cycle = d.cycle\nLEFT JOIN (\n  SELECT CASE\n      WHEN date_release < '2011-07-21' THEN 'Core Set'\n      WHEN date_release >= '2011-07-21' and date_release < '2012-01-06' THEN 'Shadows of Mirkwood'\n      WHEN date_release >= '2012-01-06' and date_release < '2012-08-17' THEN 'Dwarrowdelf'\n      WHEN date_release >= '2012-08-17' and date_release < '2014-02-21' THEN 'Against the Shadow'\n      WHEN date_release >= '2014-02-21' and date_release < '2015-04-03' THEN 'The Ring-maker'\n      WHEN date_release >= '2015-04-03' and date_release < '2016-02-11' THEN 'Angmar Awakened'\n      WHEN date_release >= '2016-02-11' and date_release < '2016-11-23' THEN 'Dream-chaser'\n      WHEN date_release >= '2016-11-23' and date_release < '2018-06-14' THEN 'Haradrim'\n      WHEN date_release >= '2018-06-14' and date_release < '2019-08-02' THEN 'Ered Mithrin'\n      WHEN date_release >= '2019-08-02' and date_release < '2021-03-21' THEN 'Vengeance of Mordor'\n      ELSE 'ALeP - Oaths of the Rohirrim'\n    END AS cycle,\n    COUNT(*) AS number\n  FROM (\n    SELECT MAX(p.date_release) AS date_release,\n      d.user_id\n    FROM (\n      SELECT last_pack_id,\n        user_id\n      FROM decklist\n      WHERE date_creation LIKE '".$month."-%'\n      UNION ALL\n      SELECT d.last_pack_id,\n        d.user_id\n      FROM deck d\n      LEFT JOIN decklist dl\n      ON d.id = dl.parent_deck_id\n      WHERE dl.parent_deck_id IS NULL\n        AND d.last_pack_id IS NOT NULL\n        AND d.problem IS NULL\n        AND (('".$month."' < '2021-04' AND (d.date_update LIKE '".$month."-%' OR (d.date_creation LIKE '".$month."-%' AND d.date_update >= '2022-02-01'))) OR\n             ('".$month."' >= '2021-04' AND '".$month."' < '2022-02' AND\n              (d.date_creation LIKE '".$month."-%' OR (d.date_update LIKE '".$month."-%' AND d.date_creation < '2021-04-01'))) OR\n             ('".$month."' >= '2022-02' AND d.date_creation LIKE '".$month."-%'))\n    ) d\n    JOIN pack p\n    ON d.last_pack_id = p.id\n    GROUP BY d.user_id\n  ) t\n  GROUP BY cycle\n) u\nON c.cycle = u.cycle";
        $res_decks_created = $dbh->executeQuery($query, [])->fetchAll(\PDO::FETCH_ASSOC);
        $query = "SELECT '".$month."' AS month,\n  c.cycle,\n  IFNULL(d.number, 0) AS number_decks,\n  IFNULL(u.number, 0) AS number_users\nFROM (\n  SELECT 'Core Set' AS cycle\n  UNION\n  SELECT 'Shadows of Mirkwood' AS cycle\n  UNION\n  SELECT 'Dwarrowdelf' AS cycle\n  UNION\n  SELECT 'Against the Shadow' AS cycle\n  UNION\n  SELECT 'The Ring-maker' AS cycle\n  UNION\n  SELECT 'Angmar Awakened' AS cycle\n  UNION\n  SELECT 'Dream-chaser' AS cycle\n  UNION\n  SELECT 'Haradrim' AS cycle\n  UNION\n  SELECT 'Ered Mithrin' AS cycle\n  UNION\n  SELECT 'Vengeance of Mordor' AS cycle\n  UNION\n  SELECT 'ALeP - Oaths of the Rohirrim' AS cycle\n) c\nLEFT JOIN (\n  SELECT CASE\n      WHEN p.date_release < '2011-07-21' THEN 'Core Set'\n      WHEN p.date_release >= '2011-07-21' and p.date_release < '2012-01-06' THEN 'Shadows of Mirkwood'\n      WHEN p.date_release >= '2012-01-06' and p.date_release < '2012-08-17' THEN 'Dwarrowdelf'\n      WHEN p.date_release >= '2012-08-17' and p.date_release < '2014-02-21' THEN 'Against the Shadow'\n      WHEN p.date_release >= '2014-02-21' and p.date_release < '2015-04-03' THEN 'The Ring-maker'\n      WHEN p.date_release >= '2015-04-03' and p.date_release < '2016-02-11' THEN 'Angmar Awakened'\n      WHEN p.date_release >= '2016-02-11' and p.date_release < '2016-11-23' THEN 'Dream-chaser'\n      WHEN p.date_release >= '2016-11-23' and p.date_release < '2018-06-14' THEN 'Haradrim'\n      WHEN p.date_release >= '2018-06-14' and p.date_release < '2019-08-02' THEN 'Ered Mithrin'\n      WHEN p.date_release >= '2019-08-02' and p.date_release < '2021-03-21' THEN 'Vengeance of Mordor'\n      ELSE 'ALeP - Oaths of the Rohirrim'\n    END AS cycle,\n    COUNT(*) AS number\n  FROM (\n    SELECT dl.last_pack_id\n    FROM questlog q\n    JOIN questlog_deck qd\n    ON q.id = qd.questlog_id\n    JOIN decklist dl\n    ON qd.decklist_id = dl.id\n    WHERE q.date_played LIKE '".$month."-%'\n    UNION ALL\n    SELECT d.last_pack_id\n    FROM questlog q\n    JOIN questlog_deck qd\n    ON q.id = qd.questlog_id\n    JOIN deck d\n    ON qd.deck_id = d.id\n    WHERE qd.decklist_id IS NULL\n      AND q.date_played LIKE '".$month."-%'\n  ) d\n  JOIN pack p\n  ON d.last_pack_id = p.id\n  GROUP BY cycle\n) d\nON c.cycle = d.cycle\nLEFT JOIN (\n  SELECT CASE\n      WHEN date_release < '2011-07-21' THEN 'Core Set'\n      WHEN date_release >= '2011-07-21' and date_release < '2012-01-06' THEN 'Shadows of Mirkwood'\n      WHEN date_release >= '2012-01-06' and date_release < '2012-08-17' THEN 'Dwarrowdelf'\n      WHEN date_release >= '2012-08-17' and date_release < '2014-02-21' THEN 'Against the Shadow'\n      WHEN date_release >= '2014-02-21' and date_release < '2015-04-03' THEN 'The Ring-maker'\n      WHEN date_release >= '2015-04-03' and date_release < '2016-02-11' THEN 'Angmar Awakened'\n      WHEN date_release >= '2016-02-11' and date_release < '2016-11-23' THEN 'Dream-chaser'\n      WHEN date_release >= '2016-11-23' and date_release < '2018-06-14' THEN 'Haradrim'\n      WHEN date_release >= '2018-06-14' and date_release < '2019-08-02' THEN 'Ered Mithrin'\n      WHEN date_release >= '2019-08-02' and date_release < '2021-03-21' THEN 'Vengeance of Mordor'\n      ELSE 'ALeP - Oaths of the Rohirrim'\n    END AS cycle,\n    COUNT(*) AS number\n  FROM (\n    SELECT MAX(p.date_release) AS date_release,\n      d.user_id\n    FROM (\n      SELECT dl.last_pack_id,\n        q.user_id\n      FROM questlog q\n      JOIN questlog_deck qd\n      ON q.id = qd.questlog_id\n      JOIN decklist dl\n      ON qd.decklist_id = dl.id\n      WHERE q.date_played LIKE '".$month."-%'\n      UNION ALL\n      SELECT d.last_pack_id,\n        q.user_id\n      FROM questlog q\n      JOIN questlog_deck qd\n      ON q.id = qd.questlog_id\n      JOIN deck d\n      ON qd.deck_id = d.id\n      WHERE qd.decklist_id IS NULL\n        AND q.date_played LIKE '".$month."-%'\n    ) d\n    JOIN pack p\n    ON d.last_pack_id = p.id\n    GROUP BY d.user_id\n  ) t\n  GROUP BY cycle\n) u\nON c.cycle = u.cycle";
        $res_decks_played = $dbh->executeQuery($query, [])->fetchAll(\PDO::FETCH_ASSOC);
        $query = "SELECT '".$month."' AS month,\n  c.cycle,\n  IFNULL(d.number, 0) AS number_quests,\n  IFNULL(u.number, 0) AS number_users\nFROM (\n  SELECT 'Core Set' AS cycle\n  UNION\n  SELECT 'Shadows of Mirkwood' AS cycle\n  UNION\n  SELECT 'Dwarrowdelf' AS cycle\n  UNION\n  SELECT 'Against the Shadow' AS cycle\n  UNION\n  SELECT 'The Ring-maker' AS cycle\n  UNION\n  SELECT 'Angmar Awakened' AS cycle\n  UNION\n  SELECT 'Dream-chaser' AS cycle\n  UNION\n  SELECT 'Haradrim' AS cycle\n  UNION\n  SELECT 'Ered Mithrin' AS cycle\n  UNION\n  SELECT 'Vengeance of Mordor' AS cycle\n  UNION\n  SELECT 'ALeP - Oaths of the Rohirrim' AS cycle\n) c\nLEFT JOIN (\n  SELECT CASE\n      WHEN p.date_release < '2011-07-21' THEN 'Core Set'\n      WHEN p.date_release >= '2011-07-21' and p.date_release < '2012-01-06' THEN 'Shadows of Mirkwood'\n      WHEN p.date_release >= '2012-01-06' and p.date_release < '2012-08-17' THEN 'Dwarrowdelf'\n      WHEN p.date_release >= '2012-08-17' and p.date_release < '2014-02-21' THEN 'Against the Shadow'\n      WHEN p.date_release >= '2014-02-21' and p.date_release < '2015-04-03' THEN 'The Ring-maker'\n      WHEN p.date_release >= '2015-04-03' and p.date_release < '2016-02-11' THEN 'Angmar Awakened'\n      WHEN p.date_release >= '2016-02-11' and p.date_release < '2016-11-23' THEN 'Dream-chaser'\n      WHEN p.date_release >= '2016-11-23' and p.date_release < '2018-06-14' THEN 'Haradrim'\n      WHEN p.date_release >= '2018-06-14' and p.date_release < '2019-08-02' THEN 'Ered Mithrin'\n      WHEN p.date_release >= '2019-08-02' and p.date_release < '2021-03-21' THEN 'Vengeance of Mordor'\n      ELSE 'ALeP - Oaths of the Rohirrim'\n    END AS cycle,\n    COUNT(*) AS number\n  FROM (\n    SELECT s.pack_id AS last_pack_id\n    FROM questlog q\n    JOIN scenario s\n    ON q.scenario_id = s.id\n    WHERE q.date_played LIKE '".$month."-%'\n  ) d\n  JOIN pack p\n  ON d.last_pack_id = p.id\n  GROUP BY cycle\n) d\nON c.cycle = d.cycle\nLEFT JOIN (\n  SELECT CASE\n      WHEN date_release < '2011-07-21' THEN 'Core Set'\n      WHEN date_release >= '2011-07-21' and date_release < '2012-01-06' THEN 'Shadows of Mirkwood'\n      WHEN date_release >= '2012-01-06' and date_release < '2012-08-17' THEN 'Dwarrowdelf'\n      WHEN date_release >= '2012-08-17' and date_release < '2014-02-21' THEN 'Against the Shadow'\n      WHEN date_release >= '2014-02-21' and date_release < '2015-04-03' THEN 'The Ring-maker'\n      WHEN date_release >= '2015-04-03' and date_release < '2016-02-11' THEN 'Angmar Awakened'\n      WHEN date_release >= '2016-02-11' and date_release < '2016-11-23' THEN 'Dream-chaser'\n      WHEN date_release >= '2016-11-23' and date_release < '2018-06-14' THEN 'Haradrim'\n      WHEN date_release >= '2018-06-14' and date_release < '2019-08-02' THEN 'Ered Mithrin'\n      WHEN date_release >= '2019-08-02' and date_release < '2021-03-21' THEN 'Vengeance of Mordor'\n      ELSE 'ALeP - Oaths of the Rohirrim'\n    END AS cycle,\n    COUNT(*) AS number\n  FROM (\n    SELECT MAX(p.date_release) AS date_release,\n      d.user_id\n    FROM (\n      SELECT s.pack_id AS last_pack_id,\n        q.user_id\n      FROM questlog q\n      JOIN scenario s\n      ON q.scenario_id = s.id\n      WHERE q.date_played LIKE '".$month."-%'\n    ) d\n    JOIN pack p\n    ON d.last_pack_id = p.id\n    GROUP BY d.user_id\n  ) t\n  GROUP BY cycle\n) u\nON c.cycle = u.cycle";
        $res_quests_played = $dbh->executeQuery($query, [])->fetchAll(\PDO::FETCH_ASSOC);
        $res = ['decks_created' => $res_decks_created, 'decks_played' => $res_decks_played, 'quests_played' => $res_quests_played, 'packs' => $packs, 'pack_rules' => $pack_rules];
        $response = new Response(json_encode($res));
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    /**
     * @return Response
     *
     * @Route("/admin/stat_cards", name="app_stat_cards", methods={"GET"})
     */
    public function getStatCardsAction(Request $request)
    {
        // Per-card stats are too heavy to compute on a request worker (they scan a
        // whole month of decklistslot/deckslot and would saturate the shared
        // php-fpm pool, 504-ing all three sites). They are precomputed off-line into
        // stat_cards_cache by `app:stats:precompute-cards` (cron); here we only read.
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
        $payload = $dbh->executeQuery('SELECT payload FROM stat_cards_cache WHERE month = ? AND step = ?', [$month, (int) $step])->fetchColumn();
        if (false === $payload) {
            return new Response("Per-card stats for {$month} have not been precomputed yet. Run `php bin/console app:stats:precompute-cards {$month}` (scheduled via cron).", 503);
        }
        $response = new Response($payload);
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    /**
     * @return Response
     *
     * @Route("/admin/stat_packs", name="app_stat_packs", methods={"GET"})
     */
    public function getStatPacksAction(Request $request)
    {
        /* @var $dbh \Doctrine\DBAL\Connection */
        $packs = $this->getPacks();
        $pack_rules = $this->getPackRuless();
        $quests = $this->getQuests();
        $res = ['packs' => $packs, 'pack_rules' => $pack_rules, 'quests' => $quests];
        $response = new Response(json_encode($res));
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getPacks()
    {
        $dbh = $this->getDoctrine()->getConnection();
        $query = "SELECT name, date_release\nFROM pack\nWHERE date_release IS NOT NULL\nORDER BY date_release";
        $packs = $dbh->executeQuery($query, [])->fetchAll(\PDO::FETCH_ASSOC);

        return $packs;
    }

    /**
     * @return array<string, array{string, string}>
     */
    public function getPackRuless()
    {
        $pack_rules = ['Core Set' => ['2000-01-01', '2011-07-21'], 'Shadows of Mirkwood' => ['2011-07-21', '2012-01-06'], 'Dwarrowdelf' => ['2012-01-06', '2012-08-17'], 'Against the Shadow' => ['2012-08-17', '2014-02-21'], 'The Ring-maker' => ['2014-02-21', '2015-04-03'], 'Angmar Awakened' => ['2015-04-03', '2016-02-11'], 'Dream-chaser' => ['2016-02-11', '2016-11-23'], 'Haradrim' => ['2016-11-23', '2018-06-14'], 'Ered Mithrin' => ['2018-06-14', '2019-08-02'], 'Vengeance of Mordor' => ['2019-08-02', '2021-03-21'], 'ALeP - Oaths of the Rohirrim' => ['2021-03-21', '2099-12-31']];

        return $pack_rules;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getQuests()
    {
        $dbh = $this->getDoctrine()->getConnection();
        $query = "SELECT p.name, GROUP_CONCAT(s.name SEPARATOR ';') AS quests\nFROM scenario s\nJOIN pack p\nON s.pack_id = p.id\nGROUP BY p.name\nORDER BY p.name";
        $quests = $dbh->executeQuery($query, [])->fetchAll(\PDO::FETCH_ASSOC);
        for ($i = 0; $i < count($quests); ++$i) {
            $quests[$i]['quests'] = explode(';', $quests[$i]['quests']);
        }

        return $quests;
    }

    /**
     * @return array<int|string, mixed>
     */
    public function getOctgnIdMapping()
    {
        $dbh = $this->getDoctrine()->getConnection();
        $query = "SELECT cprim1.octgnid AS id1,\n  cprim2.octgnid AS id2\nFROM card c1\nJOIN (SELECT cpx.card_id, cpx.pack_id, cpx.octgnid FROM card_printing cpx WHERE cpx.id = (SELECT cpy.id FROM card_printing cpy JOIN pack py ON py.id = cpy.pack_id WHERE cpy.card_id = cpx.card_id ORDER BY (py.date_release IS NULL), py.date_release, cpy.position, cpy.id LIMIT 1)) cprim1 ON cprim1.card_id = c1.id\nJOIN pack p\nON p.id = cprim1.pack_id\nJOIN card c2\nON source_code(c1.code, p.name) = c2.code\nJOIN (SELECT cpx.card_id, cpx.pack_id, cpx.octgnid FROM card_printing cpx WHERE cpx.id = (SELECT cpy.id FROM card_printing cpy JOIN pack py ON py.id = cpy.pack_id WHERE cpy.card_id = cpx.card_id ORDER BY (py.date_release IS NULL), py.date_release, cpy.position, cpy.id LIMIT 1)) cprim2 ON cprim2.card_id = c2.id\nWHERE c1.code != c2.code\nORDER BY CAST(c1.code AS UNSIGNED)";
        $res = $dbh->executeQuery($query, [])->fetchAll(\PDO::FETCH_ASSOC);
        $mapping = [];
        for ($i = 0; $i < count($res); ++$i) {
            $mapping[$res[$i]['id1']] = $res[$i]['id2'];
        }

        return $mapping;
    }
}
