<?php

namespace AppBundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Client;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Deck lifecycle of the "test" user: create a deck, edit it, publish it as a decklist.
 *
 * Forms are submitted the way the browser does it: the deck builder's JavaScript serializes the
 * deck into the hidden "content" field of #save_form, as {"main": {code: qty}, "side": {}}.
 *
 * Everything created here is deleted in tearDown(), so the other tests (and their snapshots)
 * keep seeing the fixtures only.
 */
class DeckWorkflowTest extends WebTestCase {
    /** @var int[] */
    private $deckIds = [];

    protected function tearDown() {
        if ($this->deckIds) {
            $connection = static::createClient()->getContainer()->get('doctrine')->getConnection();
            $ids = implode(',', array_map('intval', $this->deckIds));
            $decklists = "SELECT id FROM decklist WHERE parent_deck_id IN ($ids)";
            foreach ([
                "DELETE FROM decklist_spheres WHERE decklist_id IN ($decklists)",
                "DELETE FROM decklistslot WHERE decklist_id IN ($decklists)",
                "DELETE FROM decklistsideslot WHERE decklist_id IN ($decklists)",
                "DELETE FROM decklist WHERE parent_deck_id IN ($ids)",
                "DELETE FROM deckchange WHERE deck_id IN ($ids)",
                "DELETE FROM deckslot WHERE deck_id IN ($ids)",
                "DELETE FROM decksideslot WHERE deck_id IN ($ids)",
                "DELETE FROM deck WHERE id IN ($ids)",
            ] as $sql) {
                // MySQL cannot use a subquery on the table being deleted from: resolve it first
                if (strpos($sql, $decklists) !== false) {
                    $decklistIds = $connection->fetchAll($decklists);
                    $in = $decklistIds ? implode(',', array_map('intval', array_column($decklistIds, 'id'))) : 'NULL';
                    $sql = str_replace($decklists, $in, $sql);
                }
                $connection->exec($sql);
            }
        }
        parent::tearDown();
    }

    /* ------------------------------------------------------------ helpers */

    private function createAuthenticatedClient() {
        $client = static::createClient();
        $crawler = $client->request('GET', '/login');
        $client->submit($crawler->selectButton('_submit')->form(['_username' => 'test', '_password' => 'test']));
        $this->assertTrue($client->getResponse()->isRedirect(), 'Login failed');

        return $client;
    }

    private function db(Client $client) {
        return $client->getContainer()->get('doctrine')->getConnection();
    }

    private function fetchDeck(Client $client, $id) {
        return $this->db($client)->fetchAssoc(
            'SELECT d.name, d.description_md, d.tags, d.problem, d.major_version, d.minor_version, d.user_id, p.code AS last_pack
             FROM deck d LEFT JOIN pack p ON p.id = d.last_pack_id WHERE d.id = ?',
            [$id]
        );
    }

    private function fetchSlots(Client $client, $table, $column, $id) {
        $rows = $this->db($client)->fetchAll(
            "SELECT c.code, s.quantity FROM $table s JOIN card c ON c.id = s.card_id WHERE s.$column = ? ORDER BY c.code",
            [$id]
        );

        return array_map('intval', array_column($rows, 'quantity', 'code'));
    }

    private function saveDeck(Client $client, $deckId, $name, $description, $tags, array $main) {
        $crawler = $client->request('GET', "/deck/edit/$deckId");
        $this->assertSame(200, $client->getResponse()->getStatusCode());

        $form = $crawler->filter('#save_form')->form();
        $this->assertSame((string) $deckId, $form['id']->getValue());
        $form['name'] = $name;
        $form['description'] = $description;
        $form['tags'] = $tags;
        $form['content'] = json_encode(['main' => (object) $main, 'side' => new \stdClass()]);
        $client->submit($form);

        $this->assertSame(302, $client->getResponse()->getStatusCode());
        $this->assertSame('/decks', $client->getResponse()->headers->get('Location'));
    }

    /**
     * GET /deck/new creates an empty deck and redirects to the builder.
     */
    private function createDeck(Client $client) {
        $client->request('GET', '/deck/new');
        $this->assertSame(302, $client->getResponse()->getStatusCode());
        $location = $client->getResponse()->headers->get('Location');
        $this->assertRegExp('#^/deck/edit/\d+$#', $location);
        $deckId = (int) substr($location, strlen('/deck/edit/'));
        $this->deckIds[] = $deckId;

        return $deckId;
    }

    private static function coreLeadershipDeck() {
        $main = ['01001' => 1, '01002' => 1, '01003' => 1];
        foreach (range(13, 29) as $i) {
            $main[sprintf('010%02d', $i)] = 3;
        }

        return $main;
    }

    /* -------------------------------------------------------------- tests */

    public function testCreateEditPublishDeck() {
        $client = $this->createAuthenticatedClient();
        $userId = (int) $this->db($client)->fetchColumn("SELECT id FROM user WHERE username = 'test'");

        // 1. create: an empty deck is created, then the builder opens
        $deckId = $this->createDeck($client);

        $this->assertSame([
            'name' => 'New Deck',
            'description_md' => '',
            'tags' => '',
            'problem' => 'too_few_heroes',
            'major_version' => '0',
            'minor_version' => '0',
            'user_id' => (string) $userId,
            'last_pack' => null,
        ], $this->fetchDeck($client, $deckId));
        $this->assertSame([], $this->fetchSlots($client, 'deckslot', 'deck_id', $deckId));

        // 2. first save: 3 heroes + 51 cards
        $main = self::coreLeadershipDeck();
        $this->saveDeck($client, $deckId, 'PHPUnit Leadership', 'First *version*', 'leadership core', $main);

        $this->assertSame([
            'name' => 'PHPUnit Leadership',
            'description_md' => 'First *version*',
            'tags' => 'leadership core',
            'problem' => null,
            'major_version' => '0',
            'minor_version' => '1',
            'user_id' => (string) $userId,
            'last_pack' => 'Core',
        ], $this->fetchDeck($client, $deckId));
        $this->assertSame($main, $this->fetchSlots($client, 'deckslot', 'deck_id', $deckId));

        $crawler = $client->request('GET', '/decks');
        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $this->assertSame('My private decks (5/500 slots)', trim(preg_replace('/\s+/', ' ', $crawler->filter('h1')->text())));
        $this->assertContains('PHPUnit Leadership', $client->getResponse()->getContent());

        // 3. edit: rename, swap a hero (Glóin -> Gimli) and an ally (Gondorian Spearman -> Horseback Archer)
        $edited = $main;
        unset($edited['01003'], $edited['01029']);
        $edited['01004'] = 1;
        $edited['01030'] = 3;
        // more copies than the deck limit are capped
        $edited['01013'] = 5;
        ksort($edited);
        $this->saveDeck($client, $deckId, 'PHPUnit Leadership Tactics', 'Second version', 'leadership tactics', $edited);

        $expected = $edited;
        $expected['01013'] = 3;
        $this->assertSame([
            'name' => 'PHPUnit Leadership Tactics',
            'description_md' => 'Second version',
            'tags' => 'leadership tactics',
            'problem' => null,
            'major_version' => '0',
            'minor_version' => '2',
            'user_id' => (string) $userId,
            'last_pack' => 'Core',
        ], $this->fetchDeck($client, $deckId));
        $this->assertSame($expected, $this->fetchSlots($client, 'deckslot', 'deck_id', $deckId));

        $changes = $this->db($client)->fetchAll('SELECT variation, is_saved, version FROM deckchange WHERE deck_id = ? ORDER BY id', [$deckId]);
        // one history entry per save: [main added, main removed, side added, side removed]
        $this->assertSame([
            [
                'variation' => json_encode([$main, [], [], []]),
                'is_saved' => '1',
                'version' => '0.1',
            ],
            [
                'variation' => json_encode([['01004' => 1, '01030' => 3], ['01003' => 1, '01029' => 3], [], []]),
                'is_saved' => '1',
                'version' => '0.2',
            ],
        ], $changes);

        // 4. publish: the form is prefilled with the deck's name and description
        $crawler = $client->request('GET', "/deck/publish/$deckId");
        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $form = $crawler->filter('form[action="/decklist/create"]')->form();
        $this->assertSame((string) $deckId, $form['deck_id']->getValue());
        $this->assertSame('PHPUnit Leadership Tactics', $form['name']->getValue());
        $this->assertSame('Second version', $form['descriptionMd']->getValue());
        $this->assertSame('', $form['precedent']->getValue());

        $form['name'] = 'PHPUnit Published';
        $form['descriptionMd'] = "Published **deck**";
        $client->submit($form);

        $this->assertSame(302, $client->getResponse()->getStatusCode());
        $location = $client->getResponse()->headers->get('Location');
        $this->assertRegExp('#^/decklist/view/(\d+)/phpunitpublished-1\.0$#', $location);
        preg_match('#/view/(\d+)/#', $location, $matches);
        $decklistId = (int) $matches[1];

        $decklist = $this->db($client)->fetchAssoc(
            'SELECT name, name_canonical, version, description_md, description_html, user_id, parent_deck_id, precedent_decklist_id, nb_votes, nb_favorites, nb_comments
             FROM decklist WHERE id = ?',
            [$decklistId]
        );
        $this->assertSame([
            'name' => 'PHPUnit Published',
            'name_canonical' => 'phpunitpublished-1.0',
            'version' => '1.0',
            'description_md' => 'Published **deck**',
            'description_html' => '<p>Published <strong>deck</strong></p>',
            'user_id' => (string) $userId,
            'parent_deck_id' => (string) $deckId,
            'precedent_decklist_id' => null,
            'nb_votes' => '0',
            'nb_favorites' => '0',
            'nb_comments' => '0',
        ], $decklist);
        $this->assertSame($expected, $this->fetchSlots($client, 'decklistslot', 'decklist_id', $decklistId));

        // the decklist is published as 1.0, and the deck moves on to 1.1
        $deck = $this->fetchDeck($client, $deckId);
        $this->assertSame(['1', '1'], [$deck['major_version'], $deck['minor_version']]);

        // 5. the decklist is public
        $client->request('GET', '/logout');
        $crawler = $client->request('GET', $location);
        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $this->assertSame('PHPUnit Published · RingsDB', trim($crawler->filter('title')->text()));
        $this->assertContains('Published deck', $crawler->filter('body')->text());
    }

    /**
     * Production behaviour, kept on purpose: the "Cannot import an empty deck" guard only
     * rejects a "content" without cards when "main" is missing or decodes to an empty array.
     * The builder sends {"main": {}, "side": {}}: "main" is decoded as a stdClass, which is never
     * empty(), so the builder can save an empty deck.
     */
    public function testSavingAnEmptyDeckFromTheBuilderIsAccepted() {
        $client = $this->createAuthenticatedClient();
        $deckId = $this->createDeck($client);
        // start from a non-empty deck, to check that saving empties it
        $this->saveDeck($client, $deckId, 'PHPUnit Full', '', '', self::coreLeadershipDeck());

        $this->saveDeck($client, $deckId, 'PHPUnit Empty', '', '', []);

        $deck = $this->fetchDeck($client, $deckId);
        $this->assertSame(['PHPUnit Empty', 'too_few_heroes', null, '2'], [$deck['name'], $deck['problem'], $deck['last_pack'], $deck['minor_version']]);
        $this->assertSame([], $this->fetchSlots($client, 'deckslot', 'deck_id', $deckId));

        $client->request('GET', '/decks');
        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $client->request('GET', "/deck/view/$deckId");
        $this->assertSame(200, $client->getResponse()->getStatusCode());
    }

    /**
     * @dataProvider refusedContentProvider
     */
    public function testSavingADeckWithoutCardsIsRefused($content) {
        $client = $this->createAuthenticatedClient();
        $deckId = $this->createDeck($client);

        $crawler = $client->request('GET', "/deck/edit/$deckId");
        $form = $crawler->filter('#save_form')->form();
        $form['name'] = 'PHPUnit Empty';
        $form['content'] = $content;
        $client->submit($form);

        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $this->assertSame('Cannot import an empty deck', $client->getResponse()->getContent());
        $deck = $this->fetchDeck($client, $deckId);
        $this->assertSame(['New Deck', '0'], [$deck['name'], $deck['minor_version']]);
    }

    public function refusedContentProvider() {
        return [
            'main as an empty array' => [json_encode(['main' => [], 'side' => []])],
            'no main' => [json_encode(['side' => new \stdClass()])],
            'empty field' => [''],
            'invalid JSON' => ['{"main":'],
        ];
    }

    /**
     * /deck/save-ajax (the builder's multi-deck mode): same guard as /deck/save, JSON answers.
     */
    public function testSaveAjax() {
        $client = $this->createAuthenticatedClient();
        $deckId = $this->createDeck($client);
        $post = function ($content) use ($client, $deckId) {
            $client->request('POST', '/deck/save-ajax', [
                'id' => $deckId,
                'name' => 'PHPUnit Ajax',
                'description' => '',
                'tags' => '',
                'content' => $content,
            ]);

            return $client->getResponse();
        };

        $response = $post(json_encode(['side' => new \stdClass()]));
        $this->assertSame(422, $response->getStatusCode());
        $this->assertSame(['success' => false, 'error' => 'Cannot save an empty deck.'], json_decode($response->getContent(), true));
        $this->assertSame('New Deck', $this->fetchDeck($client, $deckId)['name']);

        $main = self::coreLeadershipDeck();
        $response = $post(json_encode(['main' => $main, 'side' => new \stdClass()]));
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(['success' => true, 'id' => $deckId], json_decode($response->getContent(), true));
        $deck = $this->fetchDeck($client, $deckId);
        $this->assertSame(['PHPUnit Ajax', null, '1'], [$deck['name'], $deck['problem'], $deck['minor_version']]);
        $this->assertSame($main, $this->fetchSlots($client, 'deckslot', 'deck_id', $deckId));

        // what the builder sends for an empty deck is accepted, as on /deck/save
        $response = $post(json_encode(['main' => new \stdClass(), 'side' => new \stdClass()]));
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame([], $this->fetchSlots($client, 'deckslot', 'deck_id', $deckId));
    }

    /* -------------------------------------------------------------- import */

    /**
     * Ids of the test user's decks created since $maxId, registered for deletion in tearDown().
     */
    private function newDeckIds(Client $client, $maxId) {
        $ids = array_map('intval', array_column($this->db($client)->fetchAll('SELECT id FROM deck WHERE id > ? ORDER BY id', [$maxId]), 'id'));
        $this->deckIds = array_merge($this->deckIds, $ids);

        return $ids;
    }

    private function maxDeckId(Client $client) {
        return (int) $this->db($client)->fetchColumn('SELECT MAX(id) FROM deck');
    }

    /**
     * The import page parses the pasted text in JavaScript and posts the result, as the builder
     * does, to /deck/save: {"main": {...}, "side": {...}}, or an empty "content" if nothing was
     * pasted.
     *
     * @dataProvider importProvider
     */
    public function testImportPage($content, $expectedSlots) {
        $client = $this->createAuthenticatedClient();
        $maxId = $this->maxDeckId($client);

        $crawler = $client->request('GET', '/deck/import');
        $form = $crawler->selectButton('btn-save')->form();
        $form['name'] = 'PHPUnit Import';
        $form['content'] = $content;
        $client->submit($form);

        $ids = $this->newDeckIds($client, $maxId);
        if ($expectedSlots === null) {
            $this->assertSame(200, $client->getResponse()->getStatusCode());
            $this->assertSame('Cannot import an empty deck', $client->getResponse()->getContent());
            $this->assertSame([], $ids);

            return;
        }

        $this->assertSame(302, $client->getResponse()->getStatusCode());
        $this->assertSame('/decks', $client->getResponse()->headers->get('Location'));
        $this->assertCount(1, $ids);
        $this->assertSame('PHPUnit Import', $this->fetchDeck($client, $ids[0])['name']);
        $this->assertSame($expectedSlots, $this->fetchSlots($client, 'deckslot', 'deck_id', $ids[0]));
    }

    public function importProvider() {
        $main = self::coreLeadershipDeck();

        return [
            'a deck' => [json_encode(['main' => $main, 'side' => new \stdClass()]), $main],
            // pasted text without any recognized card: the JavaScript still sends {"main": {}}
            'no recognized card' => [json_encode(['main' => new \stdClass(), 'side' => new \stdClass()]), []],
            'nothing pasted' => ['', null],
        ];
    }

    /**
     * The file is parsed on the server and forwarded to /deck/save. A file without any
     * recognized card gives {"main": [], "side": []}, which the guard refuses.
     *
     * @dataProvider fileImportProvider
     */
    public function testFileImport($filename, $fileContent, $expectedSlots, $expectedProblem = null) {
        $client = $this->createAuthenticatedClient();
        $maxId = $this->maxDeckId($client);

        $path = tempnam(sys_get_temp_dir(), 'import');
        file_put_contents($path, $fileContent);
        $file = new UploadedFile($path, $filename, 'text/plain', filesize($path), null, true);
        $client->request('POST', '/deck/fileimport', ['type' => 'auto'], ['upfile' => $file]);
        unlink($path);

        $ids = $this->newDeckIds($client, $maxId);
        if ($expectedSlots === null) {
            $this->assertSame(200, $client->getResponse()->getStatusCode());
            $this->assertSame('Cannot import an empty deck', $client->getResponse()->getContent());
            $this->assertSame([], $ids);

            return;
        }

        $this->assertSame(302, $client->getResponse()->getStatusCode());
        $this->assertSame('/decks', $client->getResponse()->headers->get('Location'));
        $this->assertCount(1, $ids);
        $deck = $this->fetchDeck($client, $ids[0]);
        $this->assertSame([pathinfo($filename, PATHINFO_FILENAME), $expectedProblem], [$deck['name'], $deck['problem']]);
        $this->assertSame($expectedSlots, $this->fetchSlots($client, 'deckslot', 'deck_id', $ids[0]));
    }

    public function fileImportProvider() {
        return [
            'text file' => ['PHPUnit Text.txt', "1x Aragorn\n3x Guard of the Citadel\n", ['01001' => 1, '01013' => 3], 'too_few_cards'],
            // BUG: parseTextImport() queries the Card.pack field removed by the card printings refactor
            'text file with pack names' => ['PHPUnit Packs.txt', "1x Aragorn (Core Set)\n3x Guard of the Citadel (Core Set)\n", ['01001' => 1, '01013' => 3], 'too_few_cards'],
            'text file without any card' => ['PHPUnit Nothing.txt', "Nothing to see here\n", null],
        ];
    }

    /**
     * A deck exported as text ("1x Gimli (Core Set)" lines) can be imported back as is.
     *
     * @dataProvider fixtureDeckProvider
     */
    public function testTextExportCanBeImportedBack($deckId) {
        $client = $this->createAuthenticatedClient();
        $client->request('GET', "/deck/export/text/$deckId");
        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $export = $client->getResponse()->getContent();
        $maxId = $this->maxDeckId($client);

        $path = tempnam(sys_get_temp_dir(), 'import');
        file_put_contents($path, $export);
        $file = new UploadedFile($path, 'PHPUnit Roundtrip.txt', 'text/plain', filesize($path), null, true);
        $client->request('POST', '/deck/fileimport', ['type' => 'auto'], ['upfile' => $file]);
        unlink($path);

        $this->assertSame(302, $client->getResponse()->getStatusCode());
        $ids = $this->newDeckIds($client, $maxId);
        $this->assertCount(1, $ids);
        $this->assertSame(
            $this->fetchSlots($client, 'deckslot', 'deck_id', $deckId),
            $this->fetchSlots($client, 'deckslot', 'deck_id', $ids[0])
        );
    }

    public function fixtureDeckProvider() {
        return [
            'deck 1' => [1],
            'deck 2' => [2],
            'deck 3' => [3],
            'deck 4' => [4],
        ];
    }

    /* ------------------------------------------------------ decklist copy */

    /**
     * Copy a decklist into a new deck (GET /deck/copy/{decklist_id}, "Copy" button of the
     * decklist toolbar). Returns the new deck id.
     */
    private function copyDecklist(Client $client, $decklistId) {
        $maxId = $this->maxDeckId($client);
        $client->request('GET', "/deck/copy/$decklistId");
        $this->assertSame(302, $client->getResponse()->getStatusCode());
        $this->assertSame('/decks', $client->getResponse()->headers->get('Location'));
        $ids = $this->newDeckIds($client, $maxId);
        $this->assertCount(1, $ids);

        return $ids[0];
    }

    /**
     * Full circle: decklist -> copy into a deck -> edit -> publish. The new decklist is derived
     * from the original one.
     */
    public function testCopyDecklistEditAndPublishAgain() {
        $client = $this->createAuthenticatedClient();
        $userId = (string) $this->db($client)->fetchColumn("SELECT id FROM user WHERE username = 'test'");
        $original = $this->fetchSlots($client, 'decklistslot', 'decklist_id', 1);

        // 1. copy: a new deck, with the decklist's name and cards, derived from the decklist
        $deckId = $this->copyDecklist($client, 1);
        $this->assertSame([
            'name' => 'Dwarf Lore/Leadership/Tactics',
            'description_md' => '',
            'tags' => 'tactics leadership lore',
            'problem' => null,
            'major_version' => '0',
            'minor_version' => '1',
            'user_id' => $userId,
            'last_pack' => 'TMV',
        ], $this->fetchDeck($client, $deckId));
        $this->assertSame('1', $this->db($client)->fetchColumn('SELECT parent_decklist_id FROM deck WHERE id = ?', [$deckId]));
        $this->assertSame($original, $this->fetchSlots($client, 'deckslot', 'deck_id', $deckId));

        $crawler = $client->request('GET', "/deck/view/$deckId");
        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $this->assertCount(1, $crawler->filter('a[href="/decklist/view/1/dwarfloreleadershiptactics-1.0"]'));

        // 2. edit: one less Veteran Axehand, add a Blade of Gondolin
        $edited = $original;
        $edited['01028'] = 2;
        $edited['01039'] = 1;
        ksort($edited);
        $this->saveDeck($client, $deckId, 'PHPUnit Dwarves Remix', 'Remixed', 'dwarf', $edited);
        $this->assertSame('1', $this->db($client)->fetchColumn('SELECT parent_decklist_id FROM deck WHERE id = ?', [$deckId]));

        // 3. publish: the new decklist is derived from decklist 1
        $crawler = $client->request('GET', "/deck/publish/$deckId");
        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $form = $crawler->filter('form[action="/decklist/create"]')->form();
        $client->submit($form);

        $this->assertSame(302, $client->getResponse()->getStatusCode());
        $location = $client->getResponse()->headers->get('Location');
        $this->assertRegExp('#^/decklist/view/\d+/phpunitdwarvesremix-1\.0$#', $location);
        $decklist = $this->db($client)->fetchAssoc('SELECT id, name, parent_deck_id, precedent_decklist_id FROM decklist WHERE parent_deck_id = ?', [$deckId]);
        $this->assertSame(['PHPUnit Dwarves Remix', (string) $deckId, '1'], [$decklist['name'], $decklist['parent_deck_id'], $decklist['precedent_decklist_id']]);
        $this->assertSame($edited, $this->fetchSlots($client, 'decklistslot', 'decklist_id', $decklist['id']));

        // 4. both decklist pages show the link
        $crawler = $client->request('GET', $location);
        $this->assertContains('Dwarf Lore/Leadership/Tactics', $crawler->filter('#table-predecessor')->text());
        $crawler = $client->request('GET', '/decklist/view/1/dwarfloreleadershiptactics-1.0');
        $this->assertContains('PHPUnit Dwarves Remix', $crawler->filter('#table-successor')->text());
    }

    public function testAnotherUserCanCopyADecklist() {
        $client = static::createClient();
        $crawler = $client->request('GET', '/login');
        $client->submit($crawler->selectButton('_submit')->form(['_username' => 'admin', '_password' => 'admin']));
        $adminId = (string) $this->db($client)->fetchColumn("SELECT id FROM user WHERE username = 'admin'");

        $deckId = $this->copyDecklist($client, 2);

        $deck = $this->fetchDeck($client, $deckId);
        $this->assertSame(['Gondor/Dunedain Leadership/Spirit', $adminId], [$deck['name'], $deck['user_id']]);
        $this->assertSame($this->fetchSlots($client, 'decklistslot', 'decklist_id', 2), $this->fetchSlots($client, 'deckslot', 'deck_id', $deckId));
    }

    public function testCopyingAnUnknownDecklist() {
        $client = $this->createAuthenticatedClient();
        $maxId = $this->maxDeckId($client);

        $client->request('GET', '/deck/copy/999');

        $this->assertSame(404, $client->getResponse()->getStatusCode());
        $this->assertSame([], $this->newDeckIds($client, $maxId));
    }

    public function testAnonymousCannotCopyADecklist() {
        $client = static::createClient();
        $client->request('GET', '/deck/copy/1');

        $this->assertSame(302, $client->getResponse()->getStatusCode());
        $this->assertSame('http://localhost/login', $client->getResponse()->headers->get('Location'));
    }

    public function testPublishingAnInvalidDeckIsRefused() {
        $client = $this->createAuthenticatedClient();
        $deckId = $this->createDeck($client);

        // heroes only: too few cards
        $this->saveDeck($client, $deckId, 'PHPUnit Invalid', '', '', ['01001' => 1, '01002' => 1, '01003' => 1]);
        $this->assertSame('too_few_cards', $this->fetchDeck($client, $deckId)['problem']);

        $client->request('GET', "/deck/publish/$deckId");
        $this->assertSame(302, $client->getResponse()->getStatusCode());
        $this->assertSame("/deck/view/$deckId", $client->getResponse()->headers->get('Location'));
        $crawler = $client->followRedirect();
        $this->assertContains('This deck cannot be published because it is invalid.', $crawler->filter('body')->text());
        $this->assertSame('0', $this->db($client)->fetchColumn('SELECT COUNT(*) FROM decklist WHERE parent_deck_id = ?', [$deckId]));
    }

    /**
     * @dataProvider invalidDeckIdProvider
     */
    public function testPublishingAnUnknownDeckIsABadRequest(array $parameters) {
        $client = $this->createAuthenticatedClient();
        $client->request('POST', '/decklist/create', $parameters + ['name' => 'PHPUnit Unknown']);

        $this->assertSame(400, $client->getResponse()->getStatusCode());
        $this->assertSame('0', $this->db($client)->fetchColumn("SELECT COUNT(*) FROM decklist WHERE name = 'PHPUnit Unknown'"));
    }

    public function invalidDeckIdProvider() {
        return [
            'unknown deck' => [['deck_id' => 999]],
            'missing deck_id' => [[]],
        ];
    }

    public function testCannotEditOrPublishAnotherUsersDeck() {
        $client = $this->createAuthenticatedClient();
        $connection = $this->db($client);
        $adminId = $connection->fetchColumn("SELECT id FROM user WHERE username = 'admin'");
        try {
            // deck 1 of the fixtures belongs to "test": give it temporarily to "admin"
            $connection->update('deck', ['user_id' => $adminId], ['id' => 1]);

            $client->request('GET', '/deck/edit/1');
            $this->assertSame(403, $client->getResponse()->getStatusCode());

            $client->request('POST', '/deck/save', ['id' => 1, 'name' => 'Hacked', 'content' => json_encode(['main' => ['01001' => 1]])]);
            $this->assertSame(403, $client->getResponse()->getStatusCode());

            $client->request('GET', '/deck/publish/1');
            $this->assertSame(403, $client->getResponse()->getStatusCode());

            $client->request('POST', '/decklist/create', ['deck_id' => 1, 'name' => 'Hacked']);
            $this->assertSame(403, $client->getResponse()->getStatusCode());

            $this->assertSame('Dwarf Lore/Leadership/Tactics', $connection->fetchColumn('SELECT name FROM deck WHERE id = 1'));
            $this->assertSame('0', $connection->fetchColumn("SELECT COUNT(*) FROM decklist WHERE name = 'Hacked'"));
        } finally {
            $connection->update('deck', ['user_id' => 1], ['id' => 1]);
        }
    }
}
