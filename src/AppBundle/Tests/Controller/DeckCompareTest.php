<?php

namespace AppBundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Client;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Deck comparison (/deck/compare/{deck1}/{deck2}, "Compare two decks" on the My Decks page),
 * built with Diff::getSlotsDiff().
 *
 * The fixture decks share no card, so the test inserts two decks of "test" with cards in common:
 *
 *               deck A                          deck B
 *   heroes      Gimli, Legolas                  Gimli, Aragorn
 *   draw deck   3 Veteran Axehand, 2 Gondorian  1 Veteran Axehand, 3 Guard of the Citadel
 *               Spearman
 *   sideboard   2 Feint                         1 Feint, 1 Quick Strike
 *
 * For each part the page shows the cards in common (the minimum quantity, in both columns), then
 * what is left in each deck.
 */
class DeckCompareTest extends WebTestCase {
    /** card ids (the Core Set cards have the id of their number) */
    const ARAGORN = 1, GIMLI = 4, LEGOLAS = 5, GUARD_OF_THE_CITADEL = 13, VETERAN_AXEHAND = 28,
        GONDORIAN_SPEARMAN = 29, FEINT = 34, QUICK_STRIKE = 35;

    /** @var int */
    private $maxDeckId;
    /** @var int */
    private $deckA;
    /** @var int */
    private $deckB;

    protected function setUp(): void {
        $connection = $this->db(static::createClient());
        $this->maxDeckId = (int) $connection->fetchColumn('SELECT MAX(id) FROM deck');
        $this->deckA = $this->insertDeck('PHPUnit Deck A',
            [self::GIMLI => 1, self::LEGOLAS => 1, self::VETERAN_AXEHAND => 3, self::GONDORIAN_SPEARMAN => 2],
            [self::FEINT => 2]);
        $this->deckB = $this->insertDeck('PHPUnit Deck B',
            [self::GIMLI => 1, self::ARAGORN => 1, self::VETERAN_AXEHAND => 1, self::GUARD_OF_THE_CITADEL => 3],
            [self::FEINT => 1, self::QUICK_STRIKE => 1]);
    }

    protected function tearDown(): void {
        $connection = $this->db(static::createClient());
        foreach (['deckslot', 'decksideslot'] as $table) {
            $connection->exec("DELETE FROM $table WHERE deck_id > {$this->maxDeckId}");
        }
        $connection->exec("DELETE FROM deck WHERE id > {$this->maxDeckId}");
        $connection->update('user', ['is_share_decks' => 0], ['username' => 'test']);
        parent::tearDown();
    }

    /* ------------------------------------------------------------ helpers */

    /**
     * @return \Doctrine\DBAL\Connection
     */
    private function db(Client $client) {
        return $client->getContainer()->get('doctrine')->getConnection();
    }

    /**
     * @param string $username
     * @return \Symfony\Bundle\FrameworkBundle\Client
     */
    private function createAuthenticatedClient($username = 'test') {
        $client = static::createClient();
        $crawler = $client->request('GET', '/login');
        $client->submit($crawler->selectButton('_submit')->form(['_username' => $username, '_password' => $username]));
        $this->assertTrue($client->getResponse()->isRedirect(), "Login as $username failed");

        return $client;
    }

    /**
     * A copy of fixture deck 2 with the given cards ([card id => quantity]).
     * @param mixed $name
     * @return int
     */
    private function insertDeck($name, array $main, array $side) {
        $connection = $this->db(static::createClient());
        $row = $connection->fetchAssoc('SELECT * FROM deck WHERE id = 2');
        $this->assertNotFalse($row);
        unset($row['id']);
        $connection->insert('deck', ['name' => $name] + $row);
        $id = (int) $connection->lastInsertId();
        foreach ($main as $cardId => $quantity) {
            $connection->insert('deckslot', ['deck_id' => $id, 'card_id' => $cardId, 'quantity' => $quantity]);
        }
        foreach ($side as $cardId => $quantity) {
            $connection->insert('decksideslot', ['deck_id' => $id, 'card_id' => $cardId, 'quantity' => $quantity]);
        }

        return $id;
    }

    /**
     * @param mixed $table
     * @param mixed $deckId
     * @return mixed
     */
    private function slots(Client $client, $table, $deckId) {
        return $this->db($client)->fetchAll("SELECT card_id, quantity FROM $table WHERE deck_id = ? ORDER BY card_id", [$deckId]);
    }

    /**
     * @return array the text of each line of the two columns of a row of the page
     */
    private static function columns(Crawler $row) {
        return $row->filter('.col-xs-6')->each(function (Crawler $column) {
            return $column->children()->each(function (Crawler $line) {
                return trim(preg_replace('/\s+/u', ' ', $line->text()));
            });
        });
    }

    /* -------------------------------------------------------------- tests */

    public function testCompareTwoDecks(): void {
        $client = $this->createAuthenticatedClient();
        $before = [$this->slots($client, 'deckslot', $this->deckA), $this->slots($client, 'decksideslot', $this->deckB)];

        $crawler = $client->request('GET', "/deck/compare/{$this->deckA}/{$this->deckB}");

        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $rows = $crawler->filter('.main > .row');
        $this->assertSame([["Deck #{$this->deckA}", 'PHPUnit Deck A'], ["Deck #{$this->deckB}", 'PHPUnit Deck B']], $rows->eq(0)->filter('.col-xs-6')->each(function (Crawler $column) {
            return [trim($column->filter('h1')->text()), trim($column->filter('h3')->text())];
        }));

        // heroes: in common, then left in each deck
        $this->assertSame('Heroes', trim($rows->eq(1)->text()));
        $this->assertSame([['1x Gimli'], ['1x Gimli']], self::columns($rows->eq(2)));
        $this->assertSame([['1x Legolas'], ['1x Aragorn']], self::columns($rows->eq(3)));

        // draw decks: the common quantity is the minimum, the rest stays in the deck
        $this->assertSame('Draw Decks', trim($rows->eq(4)->text()));
        $this->assertSame([['1x Veteran Axehand'], ['1x Veteran Axehand']], self::columns($rows->eq(5)));
        $this->assertSame([['2x Veteran Axehand', '2x Gondorian Spearman'], ['3x Guard of the Citadel']], self::columns($rows->eq(6)));

        // sideboards
        $this->assertSame('Sideboard', trim($rows->eq(7)->text()));
        $this->assertSame([['1x Feint'], ['1x Feint']], self::columns($rows->eq(8)));
        $this->assertSame([['1x Feint'], ['1x Quick Strike']], self::columns($rows->eq(9)));

        // the slots are detached before being changed: the decks are not modified
        $this->assertSame($before, [$this->slots($client, 'deckslot', $this->deckA), $this->slots($client, 'decksideslot', $this->deckB)]);
    }

    public function testCompareADeckWithItself(): void {
        $client = $this->createAuthenticatedClient();
        $crawler = $client->request('GET', "/deck/compare/{$this->deckA}/{$this->deckA}");

        $rows = $crawler->filter('.main > .row');
        $this->assertSame([['1x Gimli', '1x Legolas'], ['1x Gimli', '1x Legolas']], self::columns($rows->eq(2)));
        // nothing is left
        $this->assertSame([[], []], self::columns($rows->eq(3)));
        $this->assertSame([[], []], self::columns($rows->eq(6)));
        $this->assertSame([[], []], self::columns($rows->eq(9)));
    }

    public function testAnotherUsersDecksRequireSharing(): void {
        $client = $this->createAuthenticatedClient('admin');

        $client->request('GET', "/deck/compare/{$this->deckA}/{$this->deckB}");
        $this->assertSame(403, $client->getResponse()->getStatusCode());

        $this->db($client)->update('user', ['is_share_decks' => 1], ['username' => 'test']);
        $client->request('GET', "/deck/compare/{$this->deckA}/{$this->deckB}");
        $this->assertSame(200, $client->getResponse()->getStatusCode());
    }

    public function testUnknownDeck(): void {
        $client = $this->createAuthenticatedClient();
        $client->request('GET', "/deck/compare/{$this->deckA}/999");

        $this->assertSame(404, $client->getResponse()->getStatusCode());
    }
}
