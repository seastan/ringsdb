<?php

namespace AppBundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Client;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Form;

/**
 * Fellowship forms: create (GET /fellowship/new/... + POST /fellowship/save), edit, publish
 * (GET + POST /fellowship/publish), delete.
 *
 * In the browser, the deck picker (app.deck_selection.js) fills the hidden "deckN_id" and
 * "deckN_is_decklist" fields; the tests fill them directly.
 *
 * Fixtures: decks 1-4 of "test" (versions 1.1), each published as decklist 1-4; fellowship 1
 * (public) with decks 1-4. Publishing a fellowship publishes its decks, which changes the
 * fixture decks: everything is restored in tearDown().
 */
class FellowshipWorkflowTest extends WebTestCase {
    /** @var int[] max ids before the test, by table */
    private $maxIds = [];
    /** @var array */
    private $fixtureDecks;
    /** @var array */
    private $fixtureFellowship;

    protected function setUp() {
        $connection = $this->db(static::createClient());
        foreach (['fellowship', 'fellowship_deck', 'fellowship_decklist', 'decklist', 'deck'] as $table) {
            $this->maxIds[$table] = (int) $connection->fetchColumn("SELECT MAX(id) FROM $table");
        }
        $this->fixtureDecks = $connection->fetchAll('SELECT id, major_version, minor_version, date_update FROM deck');
        $this->fixtureFellowship = $this->fellowshipOneState($connection);
    }

    private function fellowshipOneState($connection) {
        return [
            $connection->fetchAssoc('SELECT name, is_public, nb_decks, nb_votes, nb_favorites, nb_comments FROM fellowship WHERE id = 1'),
            $connection->fetchAll('SELECT deck_id, deck_number FROM fellowship_deck WHERE fellowship_id = 1 ORDER BY deck_number'),
            $connection->fetchAll('SELECT decklist_id, deck_number FROM fellowship_decklist WHERE fellowship_id = 1 ORDER BY deck_number'),
        ];
    }

    protected function tearDown() {
        $connection = $this->db(static::createClient());
        $max = $this->maxIds;
        foreach ([
            "DELETE FROM fellowship_decklist WHERE id > {$max['fellowship_decklist']} OR fellowship_id > {$max['fellowship']}",
            "DELETE FROM fellowship_deck WHERE id > {$max['fellowship_deck']} OR fellowship_id > {$max['fellowship']}",
            "DELETE FROM fellowship WHERE id > {$max['fellowship']}",
            "DELETE FROM decklist_spheres WHERE decklist_id > {$max['decklist']}",
            "DELETE FROM decklistslot WHERE decklist_id > {$max['decklist']}",
            "DELETE FROM decklistsideslot WHERE decklist_id > {$max['decklist']}",
            "DELETE FROM decklist WHERE id > {$max['decklist']}",
            "DELETE FROM deckchange WHERE deck_id > {$max['deck']}",
            "DELETE FROM deckslot WHERE deck_id > {$max['deck']}",
            "DELETE FROM decksideslot WHERE deck_id > {$max['deck']}",
            "DELETE FROM deck WHERE id > {$max['deck']}",
        ] as $sql) {
            $connection->exec($sql);
        }
        foreach ($this->fixtureDecks as $deck) {
            $connection->update('deck', $deck, ['id' => $deck['id']]);
        }
        // fellowship 1 of the fixtures, if a test changed it
        if ($this->fellowshipOneState($connection) != $this->fixtureFellowship) {
            list($fellowship, $decks, $decklists) = $this->fixtureFellowship;
            $connection->update('fellowship', $fellowship, ['id' => 1]);
            $connection->exec('DELETE FROM fellowship_deck WHERE fellowship_id = 1');
            $connection->exec('DELETE FROM fellowship_decklist WHERE fellowship_id = 1');
            foreach ($decks as $row) {
                $connection->insert('fellowship_deck', $row + ['fellowship_id' => 1]);
            }
            foreach ($decklists as $row) {
                $connection->insert('fellowship_decklist', $row + ['fellowship_id' => 1]);
            }
        }
        $connection->update('user', ['is_share_decks' => 0], ['username' => 'test']);
        parent::tearDown();
    }

    /* ------------------------------------------------------------ helpers */

    private function db(Client $client) {
        return $client->getContainer()->get('doctrine')->getConnection();
    }

    private function createAuthenticatedClient($username = 'test') {
        $client = static::createClient();
        $crawler = $client->request('GET', '/login');
        $client->submit($crawler->selectButton('_submit')->form(['_username' => $username, '_password' => $username]));
        $this->assertTrue($client->getResponse()->isRedirect(), "Login as $username failed");

        return $client;
    }

    /**
     * Fills the deck picker's hidden fields: [slot => [id, is_decklist]].
     */
    private static function selectDecks(Form $form, array $decks) {
        for ($i = 1; $i <= 4; $i++) {
            $form["deck{$i}_id"] = isset($decks[$i]) ? $decks[$i][0] : '';
            $form["deck{$i}_is_decklist"] = isset($decks[$i]) ? ($decks[$i][1] ? 'true' : 'false') : '';
        }
    }

    private function fetchFellowship(Client $client, $id) {
        return $this->db($client)->fetchAssoc(
            'SELECT f.name, f.name_canonical, f.description_md, f.description_html, f.is_public, f.nb_decks, u.username, f.date_publish IS NOT NULL AS published
             FROM fellowship f JOIN user u ON u.id = f.user_id WHERE f.id = ?',
            [$id]
        );
    }

    /**
     * @return array [deck number => 'deck:<id>' | 'decklist:<id>']
     */
    private function fetchFellowshipDecks(Client $client, $id) {
        $decks = [];
        foreach ($this->db($client)->fetchAll('SELECT deck_number, deck_id FROM fellowship_deck WHERE fellowship_id = ?', [$id]) as $row) {
            $decks[(int) $row['deck_number']] = 'deck:' . $row['deck_id'];
        }
        foreach ($this->db($client)->fetchAll('SELECT deck_number, decklist_id FROM fellowship_decklist WHERE fellowship_id = ?', [$id]) as $row) {
            $decks[(int) $row['deck_number']] = 'decklist:' . $row['decklist_id'];
        }
        ksort($decks);

        return $decks;
    }

    /**
     * Creates a fellowship through the form, returns its id.
     */
    private function createFellowship(Client $client, $name, array $decks, $description = '') {
        $crawler = $client->request('GET', '/fellowship/new/0/0/0/0');
        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $form = $crawler->filter('form[action="/fellowship/save"]')->form();
        $form['name'] = $name;
        $form['descriptionMd'] = $description;
        self::selectDecks($form, $decks);
        $client->submit($form);

        $this->assertSame(302, $client->getResponse()->getStatusCode());
        $this->assertRegExp('#^/fellowship/view/\d+$#', $client->getResponse()->headers->get('Location'));

        return (int) substr($client->getResponse()->headers->get('Location'), strlen('/fellowship/view/'));
    }

    /* -------------------------------------------------------------- tests */

    public function testCreateEditPublishFellowship() {
        $client = $this->createAuthenticatedClient();

        // 1. create with decks 1 and 2
        $id = $this->createFellowship($client, 'PHPUnit Fellowship', [1 => [1, false], 2 => [2, false]], 'Two *decks*');
        $this->assertSame([
            'name' => 'PHPUnit Fellowship',
            'name_canonical' => 'phpunitfellowship',
            'description_md' => 'Two *decks*',
            'description_html' => '<p>Two <em>decks</em></p>',
            'is_public' => '0',
            'nb_decks' => '2',
            'username' => 'test',
            'published' => '0',
        ], $this->fetchFellowship($client, $id));
        $this->assertSame([1 => 'deck:1', 2 => 'deck:2'], $this->fetchFellowshipDecks($client, $id));

        $crawler = $client->request('GET', "/fellowship/view/$id");
        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $this->assertContains('PHPUnit Fellowship', $crawler->filter('h1')->text());

        // 2. edit: the form is prefilled; rename, replace deck 2 by decklist 3 in slot 3 (slots are compacted)
        $crawler = $client->request('GET', "/fellowship/edit/$id");
        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $form = $crawler->filter('form[action="/fellowship/save"]')->form();
        $this->assertSame((string) $id, $form['fellowship_id']->getValue());
        $this->assertSame('PHPUnit Fellowship', $form['name']->getValue());
        $this->assertSame('Two *decks*', $form['descriptionMd']->getValue());
        $form['name'] = 'PHPUnit Fellowship Edited';
        self::selectDecks($form, [1 => [1, false], 3 => [3, true]]);
        $client->submit($form);

        $this->assertSame(302, $client->getResponse()->getStatusCode());
        $this->assertSame("/fellowship/view/$id", $client->getResponse()->headers->get('Location'));
        $fellowship = $this->fetchFellowship($client, $id);
        $this->assertSame(['PHPUnit Fellowship Edited', 'phpunitfellowshipedited', '2'], [$fellowship['name'], $fellowship['name_canonical'], $fellowship['nb_decks']]);
        $this->assertSame([1 => 'deck:1', 2 => 'decklist:3'], $this->fetchFellowshipDecks($client, $id));

        // 3. publish form: deck 1 already has a matching decklist (decklist 1), preselected;
        // choose instead to publish it as a new decklist
        $crawler = $client->request('GET', "/fellowship/publish/$id");
        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $form = $crawler->filter('form[action="/fellowship/publish"]')->form();
        $this->assertSame('1', $form['deck_selection_1']->getValue());
        $this->assertSame(['0', '1'], $form['deck_selection_1']->availableOptionValues());
        $form['deck_selection_1'] = '0';
        $form['name'] = 'PHPUnit Published Fellowship';
        $form['descriptionMd'] = 'Published';
        $client->submit($form);

        $this->assertSame(302, $client->getResponse()->getStatusCode());
        $this->assertSame("/fellowship/view/$id/phpunitpublishedfellowship", $client->getResponse()->headers->get('Location'));
        $this->assertSame([
            'name' => 'PHPUnit Published Fellowship',
            'name_canonical' => 'phpunitpublishedfellowship',
            'description_md' => 'Published',
            'description_html' => '<p>Published</p>',
            'is_public' => '1',
            'nb_decks' => '2',
            'username' => 'test',
            'published' => '1',
        ], $this->fetchFellowship($client, $id));

        // deck 1 was published as a new decklist, the fellowship now only references decklists
        $newDecklist = $this->db($client)->fetchAssoc('SELECT id, name, version FROM decklist WHERE id > ? AND parent_deck_id = 1', [$this->maxIds['decklist']]);
        $this->assertSame(['Dwarf Lore/Leadership/Tactics', '2.0'], [$newDecklist['name'], $newDecklist['version']]);
        $this->assertSame([1 => 'decklist:' . $newDecklist['id'], 2 => 'decklist:3'], $this->fetchFellowshipDecks($client, $id));

        // 4. a published fellowship cannot be published again, and its decks cannot be changed
        $client->request('GET', "/fellowship/publish/$id");
        $this->assertSame("/fellowship/view/$id", $client->getResponse()->headers->get('Location'));
        $crawler = $client->followRedirect();
        $this->assertContains('This fellowship is already published.', $crawler->filter('body')->text());

        $crawler = $client->request('GET', "/fellowship/edit/$id");
        $form = $crawler->filter('form[action="/fellowship/save"]')->form();
        $form['name'] = 'PHPUnit Renamed';
        self::selectDecks($form, [1 => [4, false]]);
        $client->submit($form);
        $this->assertSame('PHPUnit Renamed', $this->fetchFellowship($client, $id)['name']);
        $this->assertSame([1 => 'decklist:' . $newDecklist['id'], 2 => 'decklist:3'], $this->fetchFellowshipDecks($client, $id));

        // 5. it is public
        $client->request('GET', '/logout');
        $crawler = $client->request('GET', "/fellowship/view/$id/phpunitpublishedfellowship");
        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $this->assertContains('PHPUnit Renamed', $crawler->filter('h1')->text());
    }

    public function testPublishKeepsTheMatchingDecklistsByDefault() {
        $client = $this->createAuthenticatedClient();
        $id = $this->createFellowship($client, 'PHPUnit Matching', [1 => [1, false], 2 => [2, false]]);

        $crawler = $client->request('GET', "/fellowship/publish/$id");
        $client->submit($crawler->filter('form[action="/fellowship/publish"]')->form());

        $this->assertSame(302, $client->getResponse()->getStatusCode());
        $this->assertSame([1 => 'decklist:1', 2 => 'decklist:2'], $this->fetchFellowshipDecks($client, $id));
        $this->assertSame('0', $this->db($client)->fetchColumn('SELECT COUNT(*) FROM decklist WHERE id > ?', [$this->maxIds['decklist']]));
    }

    /**
     * BUG: "Save and Publish" (auto_publish) never publishes: saveAction tests
     * empty($fellowship->getDecks()), and a Doctrine collection is never empty().
     */
    public function testSaveAndPublishDoesNotPublish() {
        $client = $this->createAuthenticatedClient();
        $crawler = $client->request('GET', '/fellowship/new/0/0/0/0');
        $form = $crawler->filter('form[action="/fellowship/save"]')->form();
        $form['name'] = 'PHPUnit Auto';
        self::selectDecks($form, [1 => [1, true], 2 => [2, true]]);
        // the "Save and Publish" button is disabled in the HTML, the JavaScript enables it
        $client->request('POST', '/fellowship/save', $form->getValues() + ['auto_publish' => '1']);

        $this->assertSame(302, $client->getResponse()->getStatusCode());
        $id = (int) substr($client->getResponse()->headers->get('Location'), strlen('/fellowship/view/'));
        $fellowship = $this->fetchFellowship($client, $id);
        $this->assertSame(['0', '0'], [$fellowship['is_public'], $fellowship['published']]);
    }

    public function testHeroConflictsPreventPublishing() {
        $client = $this->createAuthenticatedClient();
        // Gimli, Dáin Ironfoot and Bifur in both decks
        $id = $this->createFellowship($client, 'PHPUnit Conflict', [1 => [1, false], 2 => [1, true]]);

        $client->request('GET', "/fellowship/publish/$id");
        $this->assertSame("/fellowship/view/$id", $client->getResponse()->headers->get('Location'));
        $crawler = $client->followRedirect();
        $this->assertContains('This fellowship cannot be published because it is invalid.', $crawler->filter('body')->text());

        $client->request('POST', '/fellowship/publish', ['fellowship_id' => $id, 'name' => 'PHPUnit Conflict']);
        $this->assertSame("/fellowship/view/$id", $client->getResponse()->headers->get('Location'));
        $this->assertSame('0', $this->fetchFellowship($client, $id)['is_public']);
    }

    public function testEmptyFellowshipIsRefused() {
        $client = $this->createAuthenticatedClient();
        $crawler = $client->request('GET', '/fellowship/new/0/0/0/0');
        $form = $crawler->filter('form[action="/fellowship/save"]')->form();
        $form['name'] = 'PHPUnit Empty';
        self::selectDecks($form, []);
        $client->submit($form);

        $this->assertSame(422, $client->getResponse()->getStatusCode());
        $this->assertSame('0', $this->db($client)->fetchColumn('SELECT COUNT(*) FROM fellowship WHERE id > ?', [$this->maxIds['fellowship']]));
    }

    public function testNewFellowshipFormIsPrefilledWithTheGivenDecks() {
        $client = $this->createAuthenticatedClient();
        $crawler = $client->request('GET', '/fellowship/new/1/2/0/0');

        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $this->assertContains('Decks[1] = {"id":1,', $client->getResponse()->getContent());
        $this->assertContains('Decks[2] = {"id":2,', $client->getResponse()->getContent());
        $this->assertCount(1, $crawler->filter('form[action="/fellowship/save"]'));
    }

    /* ---------------------------------------------------- other users' decks */

    public function testUsingAnotherUsersDeckRequiresSharing() {
        $client = $this->createAuthenticatedClient('admin');
        $crawler = $client->request('GET', '/fellowship/new/0/0/0/0');
        $form = $crawler->filter('form[action="/fellowship/save"]')->form();
        $form['name'] = 'PHPUnit Borrowed';
        self::selectDecks($form, [1 => [1, false]]);

        // "test" does not share their decks
        $client->submit($form);
        $this->assertSame(403, $client->getResponse()->getStatusCode());
        $this->assertSame('0', $this->db($client)->fetchColumn('SELECT COUNT(*) FROM fellowship WHERE id > ?', [$this->maxIds['fellowship']]));

        // once shared, the deck is cloned for the admin
        $this->db($client)->update('user', ['is_share_decks' => 1], ['username' => 'test']);
        $client->submit($form);
        $this->assertSame(302, $client->getResponse()->getStatusCode());
        $id = (int) substr($client->getResponse()->headers->get('Location'), strlen('/fellowship/view/'));
        $decks = $this->fetchFellowshipDecks($client, $id);
        $cloneId = (int) substr($decks[1], strlen('deck:'));
        $this->assertGreaterThan($this->maxIds['deck'], $cloneId);
        $clone = $this->db($client)->fetchAssoc('SELECT d.name, u.username FROM deck d JOIN user u ON u.id = d.user_id WHERE d.id = ?', [$cloneId]);
        $this->assertSame(['Dwarf Lore/Leadership/Tactics', 'admin'], [$clone['name'], $clone['username']]);
    }

    public function testCannotChangeAnotherUsersFellowship() {
        $client = $this->createAuthenticatedClient('admin');

        $client->request('GET', '/fellowship/edit/1');
        $this->assertSame(403, $client->getResponse()->getStatusCode());

        $client->request('POST', '/fellowship/save', ['fellowship_id' => 1, 'name' => 'Hacked', 'deck1_id' => 1, 'deck1_is_decklist' => 'true']);
        $this->assertSame(403, $client->getResponse()->getStatusCode());

        $client->request('GET', '/fellowship/publish/1');
        $this->assertSame(403, $client->getResponse()->getStatusCode());

        $client->request('POST', '/fellowship/publish', ['fellowship_id' => 1, 'name' => 'Hacked']);
        $this->assertSame(403, $client->getResponse()->getStatusCode());

        $client->request('POST', '/fellowship/delete', ['fellowship_id' => 1]);
        $this->assertSame(403, $client->getResponse()->getStatusCode());

        $this->assertSame('Heirs to Numeror Cycle', $this->fetchFellowship($client, 1)['name']);
    }

    /* ------------------------------------------------------------- delete */

    public function testDeleteFellowship() {
        $client = $this->createAuthenticatedClient();
        $id = $this->createFellowship($client, 'PHPUnit Delete', [1 => [1, false]]);

        $client->request('POST', '/fellowship/delete', ['fellowship_id' => $id]);

        $this->assertSame(302, $client->getResponse()->getStatusCode());
        $this->assertSame('/myfellowships', $client->getResponse()->headers->get('Location'));
        $this->assertFalse($this->fetchFellowship($client, $id));
        $this->assertSame([], $this->fetchFellowshipDecks($client, $id));
        // the decks themselves are kept
        $this->assertSame('1', $this->db($client)->fetchColumn('SELECT COUNT(*) FROM deck WHERE id = 1'));
    }

    public function testFellowshipWithSocialActivityCannotBeDeleted() {
        $client = $this->createAuthenticatedClient();
        $this->db($client)->update('fellowship', ['nb_votes' => 1], ['id' => 1]);

        $client->request('POST', '/fellowship/delete', ['fellowship_id' => 1]);

        $this->assertSame('/myfellowships', $client->getResponse()->headers->get('Location'));
        $crawler = $client->followRedirect();
        $this->assertContains("You can't delete a published fellowship.", $crawler->filter('body')->text());
        $this->assertSame('Heirs to Numeror Cycle', $this->fetchFellowship($client, 1)['name']);
    }

    public function testDeleteList() {
        $client = $this->createAuthenticatedClient();
        $id1 = $this->createFellowship($client, 'PHPUnit Delete 1', [1 => [1, false]]);
        $id2 = $this->createFellowship($client, 'PHPUnit Delete 2', [1 => [2, false]]);
        $this->db($client)->update('fellowship', ['nb_comments' => 1], ['id' => $id2]);

        $client->request('POST', '/fellowship/delete_list', ['ids' => "$id1-$id2-999"]);

        $this->assertSame(302, $client->getResponse()->getStatusCode());
        $this->assertSame('/myfellowships', $client->getResponse()->headers->get('Location'));
        $this->assertFalse($this->fetchFellowship($client, $id1));
        $this->assertSame('PHPUnit Delete 2', $this->fetchFellowship($client, $id2)['name']);
    }
}
