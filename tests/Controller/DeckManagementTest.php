<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Deck actions of BuilderController, other than create / edit / publish (see DeckWorkflowTest):
 * - clone (GET /deck/clone/{id}, "Clone" button of the deck page), a GET that writes;
 * - delete (POST /deck/delete, modal of the deck page and of My Decks);
 * - delete a selection (POST /deck/delete_list, "ids" = "1-2-3", My Decks);
 * - autosave (POST /deck/autosave, sent by app.deck_history.js from the deck builder);
 * - import of several decks from a zip archive (POST /deck/import/all, My Decks).
 *
 * The tests work on decks inserted for them (copies of fixture deck 2, owned by "test");
 * everything is removed or restored in tearDown().
 */
class DeckManagementTest extends WebTestCase
{
    use \App\Tests\TemporaryFileTrait;

    private KernelBrowser $client;

    /** @var int[] */
    private array $maxIds = [];

    private array $fixtureDecklists;

    private array $fixtureUsers;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $connection = $this->db();
        foreach (['deck', 'deckchange', 'fellowship', 'questlog'] as $table) {
            $this->maxIds[$table] = (int) $connection->fetchColumn("SELECT MAX(id) FROM $table");
        }

        $this->fixtureDecklists = $connection->fetchAll('SELECT * FROM decklist');
        $this->fixtureUsers = $connection->fetchAll('SELECT id, is_share_decks FROM user');
    }

    protected function tearDown(): void
    {
        $connection = $this->db();
        $max = $this->maxIds;
        foreach ($this->fixtureDecklists as $decklist) {
            $connection->update('decklist', $decklist, ['id' => $decklist['id']]);
        }

        foreach ([
            "DELETE FROM fellowship_deck WHERE fellowship_id > {$max['fellowship']} OR deck_id > {$max['deck']}",
            "DELETE FROM fellowship WHERE id > {$max['fellowship']}",
            "DELETE FROM questlog_deck WHERE questlog_id > {$max['questlog']}",
            "DELETE FROM questlog WHERE id > {$max['questlog']}",
            "DELETE FROM deckchange WHERE id > {$max['deckchange']} OR deck_id > {$max['deck']}",
            "DELETE FROM deckslot WHERE deck_id > {$max['deck']}",
            "DELETE FROM decksideslot WHERE deck_id > {$max['deck']}",
            "DELETE FROM deck WHERE id > {$max['deck']}",
        ] as $sql) {
            $connection->exec($sql);
        }

        foreach ($this->fixtureUsers as $user) {
            $connection->update('user', $user, ['id' => $user['id']]);
        }

        parent::tearDown();
    }

    /* ------------------------------------------------------------ helpers */

    private function db(): \Doctrine\DBAL\Connection
    {
        return static::getContainer()->get('doctrine')->getConnection();
    }

    private function createAuthenticatedClient(string $username = 'test'): KernelBrowser
    {
        $client = $this->client;
        $crawler = $client->request('GET', '/login');
        $client->submit($crawler->selectButton('_submit')->form(['_username' => $username, '_password' => $username]));
        $this->assertTrue($client->getResponse()->isRedirect(), "Login as $username failed");

        return $client;
    }

    /**
     * A copy of fixture deck 2 (cards included), plus 2 Feint in the sideboard, returns its id.
     */
    private function insertDeck(string $name, array $values = []): int
    {
        $connection = $this->db();
        $row = $connection->fetchAssoc('SELECT * FROM deck WHERE id = 2');
        $this->assertNotFalse($row);
        unset($row['id']);
        $connection->insert('deck', $values + ['name' => $name] + $row);
        $id = (int) $connection->lastInsertId();
        $connection->exec("INSERT INTO deckslot (deck_id, card_id, quantity) SELECT $id, card_id, quantity FROM deckslot WHERE deck_id = 2");
        $connection->insert('decksideslot', ['deck_id' => $id, 'card_id' => 34, 'quantity' => 2]);

        return $id;
    }

    /**
     * @return array<int|string, mixed>
     */
    private function slots(string $table, $deckId): array
    {
        return array_column($this->db()->fetchAll("SELECT card_id, quantity FROM $table WHERE deck_id = ? ORDER BY card_id", [$deckId]), 'quantity', 'card_id');
    }

    private function deckExists(int $id): bool
    {
        return (bool) $this->db()->fetchColumn('SELECT COUNT(*) FROM deck WHERE id = ?', [$id]);
    }

    /**
     * @return array<int, int>
     */
    private function newDeckIds(): array
    {
        return array_map('intval', array_column($this->db()->fetchAll('SELECT id FROM deck WHERE id > ? ORDER BY id', [$this->maxIds['deck']]), 'id'));
    }

    private function flashMessages(KernelBrowser $client): array
    {
        preg_match_all("/insert_alert_message\\('(\\w+)', (\"[^\"]*\")\\)/", $client->getResponse()->getContent(), $matches, PREG_SET_ORDER);

        return array_map(fn (array $match): array => [$match[1], json_decode($match[2])], $matches);
    }

    /* -------------------------------------------------------------- clone */

    public function testCloneOwnDeck(): void
    {
        $client = $this->createAuthenticatedClient();
        $source = $this->insertDeck('PHPUnit Source', ['parent_decklist_id' => 2]);

        $client->request('GET', "/deck/clone/$source");

        $this->assertSame(302, $client->getResponse()->getStatusCode());
        $this->assertSame('/decks', $client->getResponse()->headers->get('Location'));
        $clone = array_values(array_diff($this->newDeckIds(), [$source]));
        $this->assertCount(1, $clone);
        $deck = $this->db()->fetchAssoc('SELECT d.name, d.parent_decklist_id, d.major_version, d.minor_version, u.username FROM deck d JOIN user u ON u.id = d.user_id WHERE d.id = ?', [$clone[0]]);
        // a new deck, derived from the same decklist as its source
        $this->assertSame(['name' => 'PHPUnit Source (clone)', 'parent_decklist_id' => '2', 'major_version' => '0', 'minor_version' => '1', 'username' => 'test'], $deck);
        $this->assertSame($this->slots('deckslot', $source), $this->slots('deckslot', $clone[0]));
        $this->assertSame([34 => '2'], $this->slots('decksideslot', $clone[0]));
    }

    public function testCloneAnotherUsersDeck(): void
    {
        $client = $this->createAuthenticatedClient('admin');
        $source = $this->insertDeck('PHPUnit Shared');

        // not shared
        $client->request('GET', "/deck/clone/$source");
        $this->assertSame(403, $client->getResponse()->getStatusCode());
        $this->assertSame([$source], $this->newDeckIds());

        // shared: the clone belongs to the current user
        $this->db()->update('user', ['is_share_decks' => 1], ['username' => 'test']);
        $client->request('GET', "/deck/clone/$source");
        $this->assertSame('/decks', $client->getResponse()->headers->get('Location'));
        $clone = array_values(array_diff($this->newDeckIds(), [$source]))[0];
        $this->assertSame('admin', $this->db()->fetchColumn('SELECT u.username FROM deck d JOIN user u ON u.id = d.user_id WHERE d.id = ?', [$clone]));
    }

    public function testCloneUnknownDeck(): void
    {
        $client = $this->createAuthenticatedClient();
        $client->request('GET', '/deck/clone/999');

        $this->assertSame(404, $client->getResponse()->getStatusCode());
    }

    /* ------------------------------------------------------------- delete */

    public function testDelete(): void
    {
        $client = $this->createAuthenticatedClient();
        $id = $this->insertDeck('PHPUnit Delete');
        // a decklist published from it
        $this->db()->update('decklist', ['parent_deck_id' => $id], ['id' => 4]);
        $this->db()->insert('deckchange', ['deck_id' => $id, 'date_creation' => '2015-08-16 00:00:00', 'variation' => '[{},{},{},{}]', 'is_saved' => 1]);

        $client->request('POST', '/deck/delete', ['deck_id' => $id]);

        $this->assertSame(302, $client->getResponse()->getStatusCode());
        $this->assertSame('/decks', $client->getResponse()->headers->get('Location'));
        $this->assertFalse($this->deckExists($id));
        $this->assertSame([], $this->slots('deckslot', $id));
        $this->assertSame([], $this->slots('decksideslot', $id));
        $this->assertSame('0', $this->db()->fetchColumn('SELECT COUNT(*) FROM deckchange WHERE deck_id = ?', [$id]));
        // the decklist stays, detached from the deck
        $this->assertNull($this->db()->fetchColumn('SELECT parent_deck_id FROM decklist WHERE id = 4'));
        $client->followRedirect();
        $this->assertSame([['success', 'Deck deleted.']], $this->flashMessages($client));
    }

    public function testDeckOfAFellowshipCannotBeDeleted(): void
    {
        $client = $this->createAuthenticatedClient();
        $id = $this->insertDeck('PHPUnit In A Fellowship');
        $this->addToFellowship($id);

        $client->request('POST', '/deck/delete', ['deck_id' => $id]);

        $this->assertSame('/decks', $client->getResponse()->headers->get('Location'));
        $this->assertTrue($this->deckExists($id));
        $client->followRedirect();
        $this->assertSame([['danger', "You can't delete a deck that is member of a fellowship."]], $this->flashMessages($client));
    }

    private function addToFellowship(int $deckId): int
    {
        $connection = $this->db();
        $connection->insert('fellowship', ['user_id' => 1, 'name' => 'PHPUnit', 'name_canonical' => 'phpunit', 'is_public' => 0,
            'nb_decks' => 1, 'nb_votes' => 0, 'nb_favorites' => 0, 'nb_comments' => 0,
            'date_creation' => '2015-08-16 00:00:00', 'date_update' => '2015-08-16 00:00:00']);
        $fellowshipId = (int) $connection->lastInsertId();
        $connection->insert('fellowship_deck', ['fellowship_id' => $fellowshipId, 'deck_id' => $deckId, 'deck_number' => 1]);

        return $fellowshipId;
    }

    public function testDeleteAnotherUsersDeck(): void
    {
        $client = $this->createAuthenticatedClient('admin');
        $id = $this->insertDeck('PHPUnit Not Yours');

        $client->request('POST', '/deck/delete', ['deck_id' => $id]);

        $this->assertSame(403, $client->getResponse()->getStatusCode());
        $this->assertTrue($this->deckExists($id));
    }

    public function testDeleteAnUnknownDeck(): void
    {
        $client = $this->createAuthenticatedClient();
        $client->request('POST', '/deck/delete', ['deck_id' => 999]);

        $this->assertSame('/decks', $client->getResponse()->headers->get('Location'));
    }

    /* -------------------------------------------------------- delete list */

    /**
     * Other users' and unknown decks are skipped silently.
     */
    public function testDeleteList(): void
    {
        $client = $this->createAuthenticatedClient();
        $first = $this->insertDeck('PHPUnit Delete 1');
        $second = $this->insertDeck('PHPUnit Delete 2');
        $kept = $this->insertDeck('PHPUnit Kept');
        $foreign = $this->insertDeck('PHPUnit Foreign', ['user_id' => 2]);

        $client->request('POST', '/deck/delete_list', ['ids' => "$first-$second-$foreign-999"]);

        $this->assertSame('/decks', $client->getResponse()->headers->get('Location'));
        $this->assertSame([$kept, $foreign], $this->newDeckIds());
        $client->followRedirect();
        $this->assertSame([['success', 'Decks deleted.']], $this->flashMessages($client));
    }

    /**
     * BUG-ish: unlike the single delete, the list delete does not check fellowships: the deck is
     * deleted and silently removed from its fellowship (cascade remove on Deck.fellowships), which
     * keeps the same nb_decks.
     */
    public function testDeleteListIgnoresFellowships(): void
    {
        $client = $this->createAuthenticatedClient();
        $id = $this->insertDeck('PHPUnit In A Fellowship');
        $fellowship = $this->addToFellowship($id);

        $client->request('POST', '/deck/delete_list', ['ids' => "$id"]);

        $this->assertSame('/decks', $client->getResponse()->headers->get('Location'));
        $this->assertFalse($this->deckExists($id));
        $this->assertSame('0', $this->db()->fetchColumn('SELECT COUNT(*) FROM fellowship_deck WHERE fellowship_id = ?', [$fellowship]));
        $this->assertSame('1', $this->db()->fetchColumn('SELECT nb_decks FROM fellowship WHERE id = ?', [$fellowship]));
    }

    /* ----------------------------------------------------------- autosave */

    /**
     * The builder sends the changes since the last save as
     * [main added, main removed, side added, side removed]; they are stored as an unsaved history
     * entry, replaced by a saved one when the deck is saved.
     */
    public function testAutosaveThenSave(): void
    {
        $client = $this->createAuthenticatedClient();
        $id = $this->insertDeck('PHPUnit Autosave');
        $diff = [['01001' => 1], ['01013' => 3], [], []];

        $client->request('POST', '/deck/autosave', ['deck_id' => $id, 'diff' => json_encode($diff)], [], ['HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest']);

        // the answer is the date of the change
        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $this->assertRegExp('/^\d{4}-\d\d-\d\dT\d\d:\d\d:\d\d[+-]\d\d:\d\d$/', $client->getResponse()->getContent());
        $changes = $this->db()->fetchAll('SELECT variation, is_saved FROM deckchange WHERE deck_id = ?', [$id]);
        $this->assertSame([['variation' => json_encode($diff), 'is_saved' => '0']], $changes);
        // the deck itself is not changed
        $this->assertSame($this->slots('deckslot', 2), $this->slots('deckslot', $id));

        // saving the deck replaces the unsaved entries by a saved one
        $crawler = $client->request('GET', "/deck/edit/$id");
        $form = $crawler->filter('#save_form')->form();
        $form['content'] = (string) json_encode(['main' => ['01001' => 1, '01002' => 1, '01003' => 1], 'side' => []]);
        $client->submit($form);
        $changes = $this->db()->fetchAll('SELECT is_saved FROM deckchange WHERE deck_id = ?', [$id]);
        $this->assertSame([['is_saved' => '1']], $changes);
    }

    /**
     * An empty diff creates no history entry (the builder does not send empty diffs). It did
     * before PHP 7.4: the diff was decoded as objects, and count() of an object was always 1; an
     * empty diff in 2 parts read the missing parts 3 and 4 (undefined offset).
     *
     * @dataProvider emptyDiffProvider
     */
    public function testAutosaveEmptyDiff(string $diff): void
    {
        $client = $this->createAuthenticatedClient();
        $id = $this->insertDeck('PHPUnit Autosave');

        $client->request('POST', '/deck/autosave', ['deck_id' => $id, 'diff' => $diff], [], ['HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest']);

        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $this->assertSame('', $client->getResponse()->getContent());
        $this->assertSame('0', $this->db()->fetchColumn('SELECT COUNT(*) FROM deckchange WHERE deck_id = ?', [$id]));
    }

    public function emptyDiffProvider(): array
    {
        return [
            'in 4 parts' => ['[{},{},{},{}]'],
            'in 2 parts' => ['[[],[]]'],
        ];
    }

    public function testAutosaveDiffInTwoParts(): void
    {
        $client = $this->createAuthenticatedClient();
        $id = $this->insertDeck('PHPUnit Autosave');

        $client->request('POST', '/deck/autosave', ['deck_id' => $id, 'diff' => '[{"01001":1},[]]'], [], ['HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest']);

        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $this->assertSame('[{"01001":1},[]]', $this->db()->fetchColumn('SELECT variation FROM deckchange WHERE deck_id = ?', [$id]));
    }

    /**
     * @dataProvider invalidAutosaveProvider
     */
    public function testInvalidAutosave(string $username, ?int $deckId, string $diff, int $status, string $message): void
    {
        $client = $this->createAuthenticatedClient($username);
        $id = $this->insertDeck('PHPUnit Autosave');

        $client->request('POST', '/deck/autosave', ['deck_id' => $deckId ?: $id, 'diff' => $diff], [], ['HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest']);

        // BUG (CoreExceptionListener): the 422 / 403 HTTP exceptions become 500s for AJAX requests
        $this->assertSame($status, $client->getResponse()->getStatusCode());
        $this->assertSame(['success' => false, 'message' => $message], json_decode($client->getResponse()->getContent(), true));
        $this->assertSame('0', $this->db()->fetchColumn('SELECT COUNT(*) FROM deckchange WHERE deck_id = ?', [$id]));
    }

    public function invalidAutosaveProvider(): array
    {
        return [
            'unknown deck' => ['test', 999, '[[],[],[],[]]', 500, 'Cannot find deck 999'],
            "another user's deck" => ['admin', null, '[{"01001":1},[],[],[]]', 500, "You don't have access to this deck."],
            'wrong diff' => ['test', null, '[{"01001":1}]', 500, 'Wrong content [{"01001":1}]'],
        ];
    }

    /* ----------------------------------------------------- import archive */

    /**
     * Downloads an export of a fixture deck ("text" or "octgn").
     */
    private function export(KernelBrowser $client, string $format, int $deckId): string
    {
        $client->request('GET', "/deck/export/$format/$deckId");
        $this->assertSame(200, $client->getResponse()->getStatusCode());

        return $client->getResponse()->getContent();
    }

    /**
     * Posts a zip archive of [name => content] to POST /deck/import/all ("Import from an archive"
     * modal of My Decks).
     */
    private function uploadArchive(KernelBrowser $client, array $entries): \Symfony\Component\HttpFoundation\Response
    {
        $file = self::temporaryFile('archive');
        $zip = new \ZipArchive();
        $zip->open($file, \ZipArchive::OVERWRITE);
        foreach ($entries as $name => $content) {
            $zip->addFromString($name, $content);
        }

        $zip->close();

        $client->request('POST', '/deck/import/all', [], ['uparchive' => new \Symfony\Component\HttpFoundation\File\UploadedFile($file, 'decks.zip', null, null, true)]);
        unlink($file);

        return $client->getResponse();
    }

    /**
     * One deck per file of the archive, named after the file (without folder nor extension).
     */
    public function testImportAnArchive(): void
    {
        $client = $this->createAuthenticatedClient();

        $response = $this->uploadArchive($client, [
            'Dwarves.txt' => $this->export($client, 'text', 1),
            'folder/Gondor.txt' => $this->export($client, 'text', 2),
            'Nothing.txt' => "Nothing to see here\n",
        ]);

        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame('/decks', $response->headers->get('Location'));
        $decks = $this->db()->fetchAll('SELECT id, name, user_id FROM deck WHERE id > ? ORDER BY id', [$this->maxIds['deck']]);
        $this->assertSame(['Dwarves', 'Gondor', 'Nothing'], array_column($decks, 'name'));
        $this->assertSame(['1', '1', '1'], array_column($decks, 'user_id'));
        // the cards of the exported decks come back; a file without any card gives an empty deck
        $this->assertSame($this->slots('deckslot', 1), $this->slots('deckslot', $decks[0]['id']));
        $this->assertSame($this->slots('deckslot', 2), $this->slots('deckslot', $decks[1]['id']));
        $this->assertSame([], $this->slots('deckslot', $decks[2]['id']));
        $client->followRedirect();
        $this->assertSame([['success', 'Decks imported.']], $this->flashMessages($client));
    }

    /**
     * The .o8d files of an archive are read by the OCTGN parser. Fixed: it looked the cards up by
     * Card.octgnid, moved to CardPrinting by the card printings refactor (500, nothing imported).
     */
    public function testImportAnArchiveWithAnOctgnFile(): void
    {
        $client = $this->createAuthenticatedClient();

        $response = $this->uploadArchive($client, [
            'Dwarves.txt' => $this->export($client, 'text', 1),
            'Noldor.o8d' => $this->export($client, 'octgn', 3),
        ]);

        $this->assertSame(302, $response->getStatusCode());
        $decks = $this->db()->fetchAll('SELECT id, name FROM deck WHERE id > ? ORDER BY id', [$this->maxIds['deck']]);
        $this->assertSame(['Dwarves', 'Noldor'], array_column($decks, 'name'));
        $this->assertSame($this->slots('deckslot', 1), $this->slots('deckslot', $decks[0]['id']));
        $this->assertSame($this->slots('deckslot', 3), $this->slots('deckslot', $decks[1]['id']));
    }

    public function testImportSomethingElseThanAnArchive(): void
    {
        $client = $this->createAuthenticatedClient();
        $file = self::temporaryFile('archive');
        file_put_contents($file, "1x Aragorn\n");

        $client->request('POST', '/deck/import/all', [], ['uparchive' => new \Symfony\Component\HttpFoundation\File\UploadedFile($file, 'decks.zip', null, null, true)]);
        unlink($file);

        $this->assertSame(422, $client->getResponse()->getStatusCode());
        $this->assertSame([], $this->newDeckIds());

        $client->request('POST', '/deck/import/all');
        $this->assertSame(422, $client->getResponse()->getStatusCode());
    }

    /* ------------------------------------------------------------ access */

    /**
     * @dataProvider anonymousRouteProvider
     */
    public function testAnonymousIsRedirectedToLogin(string $method, string $uri): void
    {
        $client = $this->client;
        $client->request($method, $uri, ['deck_id' => 1, 'ids' => '1', 'diff' => '[[],[],[],[]]']);

        $this->assertSame(302, $client->getResponse()->getStatusCode());
        $this->assertSame('http://localhost/login', $client->getResponse()->headers->get('Location'));
        $this->assertTrue($this->deckExists(1));
    }

    public function anonymousRouteProvider(): array
    {
        return [
            'clone' => ['GET', '/deck/clone/1'],
            'delete' => ['POST', '/deck/delete'],
            'delete list' => ['POST', '/deck/delete_list'],
            'autosave' => ['POST', '/deck/autosave'],
            'import archive' => ['POST', '/deck/import/all'],
        ];
    }
}
