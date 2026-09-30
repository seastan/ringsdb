<?php

namespace AppBundle\Tests\Command;

use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * app:suggestions computes which cards are used together and writes web/suggestions.json, loaded
 * by the deck builder (app.suggestions-statistics.js, app.suggestions-mixed.js):
 * - "index": the codes of the cards used in at least one deck (all decks, private or not),
 *   ordered by card id;
 * - "matrix": lower triangular, matrix[i][j] = number of decks with both cards i and j, divided
 *   by max(100, min(number of decks of i, number of decks of j)), in percent, rounded. With
 *   fewer than 100 decks per card, it is the number of decks with both cards.
 *
 * The command always writes web/suggestions.json: the file is saved in setUp() and restored in
 * tearDown(). The decks inserted by the tests are deleted.
 */
class SuggestionsCommandTest extends KernelTestCase {
    const ARAGORN = 1, GIMLI = 4;

    /** @var Connection */
    private $connection;
    /** @var string */
    private $file;
    /** @var string|null */
    private $backup;
    /** @var int */
    private $maxDeckId;

    protected function setUp(): void {
        static::bootKernel();
        $this->connection = static::$kernel->getContainer()->get('doctrine')->getConnection();
        $this->maxDeckId = (int) $this->connection->fetchColumn('SELECT MAX(id) FROM deck');
        $this->file = static::$kernel->getRootDir() . '/../web/suggestions.json';
        $this->backup = file_exists($this->file) ? (string) file_get_contents($this->file) : null;
    }

    protected function tearDown(): void {
        if ($this->backup === null) {
            @unlink($this->file);
        } else {
            file_put_contents($this->file, $this->backup);
        }
        $this->connection->exec("DELETE FROM deckslot WHERE deck_id > {$this->maxDeckId}");
        $this->connection->exec("DELETE FROM deck WHERE id > {$this->maxDeckId}");
        parent::tearDown();
    }

    /* ------------------------------------------------------------ helpers */

    /**
     * @return array the decoded suggestions.json
     */
    private function runCommand() {
        $application = new Application(static::$kernel);
        $tester = new CommandTester($application->find('app:suggestions'));

        $this->assertSame(0, (int) $tester->execute(['command' => 'app:suggestions']));
        $this->assertSame("done\n", $tester->getDisplay());
        $this->assertFileExists($this->file);

        return json_decode((string) file_get_contents($this->file), true);
    }

    private function insertDeck(array $cardIds): void {
        $row = $this->connection->fetchAssoc('SELECT * FROM deck WHERE id = 2');
        $this->assertNotFalse($row);
        unset($row['id']);
        $this->connection->insert('deck', ['name' => 'PHPUnit Suggestions'] + $row);
        $id = (int) $this->connection->lastInsertId();
        foreach ($cardIds as $cardId) {
            $this->connection->insert('deckslot', ['deck_id' => $id, 'card_id' => $cardId, 'quantity' => 1]);
        }
    }

    /**
     * The value of the matrix for two card codes.
     * @param mixed $code1
     * @param mixed $code2
     * @return mixed
     */
    private static function value(array $suggestions, $code1, $code2) {
        $i = array_search($code1, $suggestions['index'], true);
        $j = array_search($code2, $suggestions['index'], true);
        list($i, $j) = [max($i, $j), min($i, $j)];

        return $suggestions['matrix'][$i][$j];
    }

    /* -------------------------------------------------------------- tests */

    public function testSuggestionsOfTheFixtureDecks(): void {
        $suggestions = $this->runCommand();

        // the cards of the 4 fixture decks, by card id
        $expected = array_column($this->connection->fetchAll(
            'SELECT DISTINCT c.id, c.code FROM card c JOIN deckslot s ON s.card_id = c.id ORDER BY c.id'
        ), 'code');
        $this->assertSame(['index', 'matrix'], array_keys($suggestions));
        $this->assertSame($expected, $suggestions['index']);

        // lower triangular: row i has i values
        $this->assertCount(count($expected), $suggestions['matrix']);
        foreach ($suggestions['matrix'] as $i => $row) {
            $this->assertCount($i, $row, "row $i");
        }

        // the fixture decks share no card: 1 for two cards of the same deck, 0 otherwise
        $this->assertSame(1, self::value($suggestions, '01004', '01028'), 'Gimli and Veteran Axehand, deck 1');
        $this->assertSame(0, self::value($suggestions, '01004', '01001'), 'Gimli (deck 1) and Aragorn (deck 2)');
        $total = array_sum(array_map('array_sum', $suggestions['matrix']));
        $pairs = (int) $this->connection->fetchColumn('SELECT SUM(n * (n - 1) / 2) FROM (SELECT COUNT(*) n FROM deckslot GROUP BY deck_id) t');
        $this->assertSame($pairs, $total);
    }

    public function testCardsUsedTogether(): void {
        // Aragorn and Gimli, in two more decks
        $this->insertDeck([self::ARAGORN, self::GIMLI]);
        $this->insertDeck([self::ARAGORN, self::GIMLI]);

        $suggestions = $this->runCommand();

        $this->assertSame(2, self::value($suggestions, '01001', '01004'));
        // their pairs with the other cards of their fixture decks do not change
        $this->assertSame(1, self::value($suggestions, '01004', '01028'));
    }

    /**
     * The count is divided by the number of decks of the rarer card, when it is used in 100 decks
     * or more: 100 decks with Aragorn and Gimli, 50 of them with Guard of the Citadel too.
     */
    public function testWeightingByTheNumberOfDecks(): void {
        for ($i = 0; $i < 150; $i++) {
            $this->insertDeck($i < 50 ? [self::ARAGORN, self::GIMLI, 13] : [self::ARAGORN, self::GIMLI]);
        }

        $suggestions = $this->runCommand();

        // Aragorn: 151 decks, Gimli: 151 decks, together in 150: round(150 / 151 * 100)
        $this->assertSame(99, self::value($suggestions, '01001', '01004'));
        // Guard of the Citadel: 50 decks (+ fixture deck 2), with Aragorn in 51 of them: 51 / max(100, 51)
        $this->assertSame(51, self::value($suggestions, '01001', '01013'));
    }
}
