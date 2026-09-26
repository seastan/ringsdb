<?php

namespace AppBundle\Tests\Stats;

use AppBundle\Stats\CardStatsCalculator;
use AppBundle\Tests\Controller\JsonSnapshotTrait;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Per-card monthly usage statistics (CardStatsCalculator), precomputed by the
 * app:stats:precompute-cards command into stat_cards_cache and served by /admin/stat_cards.
 *
 * - on the fixtures (August 2015: 4 decklists whose last pack is older than 2019-08-02, so
 *   "limited" decks), the 3 steps are compared to JSON snapshots;
 * - on a controlled set of decks inserted for the test (May 2023, last pack "War of Dale",
 *   2019-11-15, so "full" decks), the counting rules are checked by hand.
 *
 * Requires the source_code() stored function (function-source-code.sql, loaded by
 * make test-fixtures). Not covered: the merging of reprints by source_code() (none of the
 * reprinted packs it handles are in the bootstrap data).
 */
class CardStatsCalculatorTest extends KernelTestCase {
    use JsonSnapshotTrait;

    const WAR_OF_DALE = 68;

    /** @var Connection */
    private $connection;
    /** @var CardStatsCalculator */
    private $calculator;
    /** @var int[] */
    private $maxIds = [];

    protected function setUp() {
        static::bootKernel();
        $this->connection = static::$kernel->getContainer()->get('doctrine')->getConnection();
        $this->calculator = static::$kernel->getContainer()->get('app.card_stats');
        foreach (['deck', 'decklist'] as $table) {
            $this->maxIds[$table] = (int) $this->connection->fetchColumn("SELECT MAX(id) FROM $table");
        }
    }

    protected function tearDown() {
        $max = $this->maxIds;
        foreach ([
            "DELETE FROM decklistslot WHERE decklist_id > {$max['decklist']}",
            "DELETE FROM decklist WHERE id > {$max['decklist']}",
            "DELETE FROM deckslot WHERE deck_id > {$max['deck']}",
            "DELETE FROM decksideslot WHERE deck_id > {$max['deck']}",
            "DELETE FROM deck WHERE id > {$max['deck']}",
            "DELETE FROM stat_cards_cache WHERE month IN ('2015-07', '2015-08', '2023-05')",
        ] as $sql) {
            $this->connection->exec($sql);
        }
        parent::tearDown();
    }

    /* ------------------------------------------------------------ helpers */

    /**
     * Inserts a private deck (a copy of fixture deck 2 with other values), returns its id.
     *
     * @param array $main [card id => quantity]
     * @param array $side [card id => quantity]
     */
    private function insertDeck(array $values, array $main, array $side = []) {
        $row = $this->connection->fetchAssoc('SELECT * FROM deck WHERE id = 2');
        unset($row['id']);
        $this->connection->insert('deck', $values + ['last_pack_id' => self::WAR_OF_DALE, 'problem' => null] + $row);
        $id = (int) $this->connection->lastInsertId();
        foreach ($main as $cardId => $quantity) {
            $this->connection->insert('deckslot', ['deck_id' => $id, 'card_id' => $cardId, 'quantity' => $quantity]);
        }
        foreach ($side as $cardId => $quantity) {
            $this->connection->insert('decksideslot', ['deck_id' => $id, 'card_id' => $cardId, 'quantity' => $quantity]);
        }

        return $id;
    }

    /**
     * Inserts a decklist (a copy of fixture decklist 2 with other values), returns its id.
     */
    private function insertDecklist(array $values, array $main) {
        $row = $this->connection->fetchAssoc('SELECT * FROM decklist WHERE id = 2');
        unset($row['id']);
        $this->connection->insert('decklist', $values + ['last_pack_id' => self::WAR_OF_DALE] + $row);
        $id = (int) $this->connection->lastInsertId();
        foreach ($main as $cardId => $quantity) {
            $this->connection->insert('decklistslot', ['decklist_id' => $id, 'card_id' => $cardId, 'quantity' => $quantity]);
        }

        return $id;
    }

    /**
     * @return array [code => [columns...]] for the given codes
     */
    private static function byCode(array $cards, array $codes, array $columns) {
        $result = [];
        foreach ($cards as $card) {
            if (in_array($card['code'], $codes, true)) {
                $result[$card['code']] = array_intersect_key($card, array_flip($columns));
            }
        }
        ksort($result);

        return $result;
    }

    /* --------------------------------------------------------- fixtures */

    /**
     * @dataProvider stepProvider
     */
    public function testFixtureMonthSnapshot($step) {
        $this->assertMatchesJsonSnapshot("stats/cards_2015-08_step$step", json_encode($this->calculator->computeCards('2015-08', $step)));
    }

    public function stepProvider() {
        return ['full decks' => ['1'], 'limited decks' => ['2'], 'sideboards and totals' => ['3']];
    }

    public function testFixtureMonth() {
        // step 2: the 4 decklists are "limited" (last packs before 2019-08-02); the fixture decks
        // are all published, so they are not counted twice
        $cards = $this->calculator->computeCards('2015-08', '2')['cards'];
        $this->assertSame([
            '01001' => ['limited_decks' => '1', 'limited_deck_copies' => '1.00'],
            '01004' => ['limited_decks' => '1', 'limited_deck_copies' => '1.00'],
            '01028' => ['limited_decks' => '1', 'limited_deck_copies' => '3.00'],
        ], self::byCode($cards, ['01001', '01004', '01028'], ['limited_decks', 'limited_deck_copies']));

        $step3 = $this->calculator->computeCards('2015-08', '3');
        $this->assertSame(['full_decks' => '0', 'limited_decks' => '4', 'sides' => '4'], $step3['total']);
        $this->assertSame(['Core Set' => ['2000-01-01', '2011-07-21']], array_slice($step3['pack_rules'], 0, 1));
        $this->assertSame(['name' => 'Core Set', 'date_release' => '2011-04-20'], $step3['packs'][0]);
        // cards released after the month are not listed
        $this->assertSame([], self::byCode($step3['cards'], ['22134'], ['code']));
    }

    public function testUnknownStep() {
        $this->assertNull($this->calculator->computeCards('2015-08', '4'));
    }

    /**
     * The temporary tables are dropped at each call: the command computes several months and
     * steps on the same connection.
     */
    public function testRepeatedCallsOnTheSameConnection() {
        $first = $this->calculator->computeCards('2015-08', '1');
        $this->calculator->computeCards('2015-08', '2');
        $this->assertSame($first, $this->calculator->computeCards('2015-08', '1'));
    }

    /* -------------------------------------------------- counting rules */

    public function testCountingRules() {
        // cards: 1 Aragorn, 13 Guard of the Citadel, 14 Faramir, 20 Ever Vigilant, 985 (MotK) Faramir
        $this->insertDeck(['date_creation' => '2023-05-10 12:00:00', 'date_update' => '2023-05-10 12:00:00'],
            [1 => 1, 13 => 5, 985 => 1], [20 => 2]);
        $this->insertDecklist(['date_creation' => '2023-05-20 12:00:00'], [13 => 2, 14 => 1]);
        // not counted: invalid deck, deck of another month, deck with an old last pack (limited)
        $this->insertDeck(['date_creation' => '2023-05-11 12:00:00', 'problem' => 'too_few_cards'], [1 => 1]);
        $this->insertDeck(['date_creation' => '2023-04-30 12:00:00'], [1 => 1]);
        $this->insertDeck(['date_creation' => '2023-05-12 12:00:00', 'last_pack_id' => 1], [1 => 1]);

        $full = $this->calculator->computeCards('2023-05', '1')['cards'];
        $this->assertSame([
            // counted once per deck
            '01001' => ['full_decks' => '1', 'full_deck_copies' => '1.00'],
            // more than 3 copies count as 3: (3 + 2) / 2
            '01013' => ['full_decks' => '2', 'full_deck_copies' => '2.50'],
            // the MotK hero counts as the card it copies, and implies the contract
            '01014' => ['full_decks' => '2', 'full_deck_copies' => '1.00'],
            '22134' => ['full_decks' => '1', 'full_deck_copies' => '1.00'],
        ], self::byCode($full, ['01001', '01013', '01014', '22134'], ['full_decks', 'full_deck_copies']));
        // MotK cards themselves are not listed
        $this->assertSame([], self::byCode($full, ['9901014'], ['code']));

        $limited = $this->calculator->computeCards('2023-05', '2')['cards'];
        $this->assertSame(['01001' => ['limited_decks' => '1']], self::byCode($limited, ['01001'], ['limited_decks']));

        $step3 = $this->calculator->computeCards('2023-05', '3');
        $this->assertSame(['01020' => ['sides' => '1', 'side_copies' => '2.00']], self::byCode($step3['cards'], ['01020'], ['sides', 'side_copies']));
        $this->assertSame(['full_decks' => '2', 'limited_decks' => '1', 'sides' => '3'], $step3['total']);
    }

    /**
     * Which private decks belong to a month changed in 2022: the update date before July 2022,
     * the creation or update date in July 2022, the creation date from August 2022.
     *
     * @dataProvider monthRuleProvider
     */
    public function testMonthRuleForPrivateDecks($month, $created, $updated, $counted) {
        $this->insertDeck(['date_creation' => $created, 'date_update' => $updated], [1 => 1]);

        $cards = $this->calculator->computeCards($month, '1')['cards'];
        $this->assertSame(['01001' => ['full_decks' => $counted ? '1' : '0']], self::byCode($cards, ['01001'], ['full_decks']));
    }

    public function monthRuleProvider() {
        return [
            'before 2022-07: updated in the month' => ['2021-03', '2020-01-01 00:00:00', '2021-03-15 00:00:00', true],
            'before 2022-07: only created in the month' => ['2021-03', '2021-03-15 00:00:00', '2021-06-01 00:00:00', false],
            'before 2022-07: created in the month, updated after 2022-08' => ['2021-03', '2021-03-15 00:00:00', '2022-09-01 00:00:00', true],
            '2022-07: created in the month' => ['2022-07', '2022-07-15 00:00:00', '2022-09-01 00:00:00', true],
            '2022-07: updated in the month' => ['2022-07', '2021-01-01 00:00:00', '2022-07-15 00:00:00', true],
            'from 2022-08: created in the month' => ['2023-05', '2023-05-15 00:00:00', '2024-01-01 00:00:00', true],
            'from 2022-08: only updated in the month' => ['2023-05', '2022-01-01 00:00:00', '2023-05-15 00:00:00', false],
        ];
    }

    /* ----------------------------------------------------------- command */

    private function runCommand(array $input) {
        $application = new Application(static::$kernel);
        $tester = new CommandTester($application->find('app:stats:precompute-cards'));
        $status = $tester->execute(['command' => 'app:stats:precompute-cards'] + $input);

        return [$status, $tester->getDisplay()];
    }

    public function testPrecomputeCommand() {
        list($status, $display) = $this->runCommand(['month' => '2015-08', '--months' => '2']);

        $this->assertSame(0, $status);
        $this->assertContains("Computing 2015-08 ...\n", $display);
        $this->assertContains("Computing 2015-07 ...\n", $display);
        $this->assertStringEndsWith("done\n", $display);
        $rows = $this->connection->fetchAll("SELECT month, step, payload FROM stat_cards_cache WHERE month IN ('2015-07', '2015-08') ORDER BY month, step");
        $this->assertSame([['2015-07', '1'], ['2015-07', '2'], ['2015-07', '3'], ['2015-08', '1'], ['2015-08', '2'], ['2015-08', '3']], array_map(function ($row) {
            return [$row['month'], $row['step']];
        }, $rows));
        // the payload is the JSON of computeCards()
        $this->assertSame(json_encode($this->calculator->computeCards('2015-08', '2')), $rows[4]['payload']);

        // running it again replaces the rows
        $this->runCommand(['month' => '2015-08']);
        $this->assertSame('6', $this->connection->fetchColumn("SELECT COUNT(*) FROM stat_cards_cache WHERE month IN ('2015-07', '2015-08')"));
    }

    public function testPrecomputeCommandRefusesAnInvalidMonth() {
        list($status, $display) = $this->runCommand(['month' => "2015-08' OR '1"]);

        $this->assertSame(1, $status);
        $this->assertContains("month must be YYYY-MM, got '2015-08' OR '1'", $display);
        $this->assertSame('0', $this->connection->fetchColumn('SELECT COUNT(*) FROM stat_cards_cache'));
    }
}
