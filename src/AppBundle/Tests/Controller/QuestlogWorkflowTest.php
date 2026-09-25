<?php

namespace AppBundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Client;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Form;

/**
 * Quest log forms: log a quest (GET /questlog/new/... + POST /questlog/save), edit, delete.
 *
 * In the browser, the deck picker (app.deck_selection.js) fills the hidden "deckN_id",
 * "deckN_is_decklist" and "questlogdeckN_content" fields: the quest log keeps its own copy of
 * each deck's cards, as {"main": {code: qty}, "side": {...}}. The tests fill them directly.
 *
 * Fixtures: decks 1-4 and decklists 1-4 of "test"; quest log 1 (public, Passage Through
 * Mirkwood) with those 4 decks. Everything is restored in tearDown().
 *
 * @coversNothing
 */
class QuestlogWorkflowTest extends WebTestCase {
    /** @var int[] max ids before the test, by table */
    private $maxIds = [];
    /** @var array */
    private $fixtureQuestlog;

    protected function setUp() {
        $connection = $this->db(static::createClient());
        foreach (['questlog', 'questlog_deck', 'deck'] as $table) {
            $this->maxIds[$table] = (int) $connection->fetchColumn("SELECT MAX(id) FROM $table");
        }
        $this->fixtureQuestlog = $this->questlogOneState($connection);
    }

    private function questlogOneState($connection) {
        return [
            $connection->fetchAssoc('SELECT * FROM questlog WHERE id = 1'),
            $connection->fetchAll('SELECT * FROM questlog_deck WHERE questlog_id = 1 ORDER BY id'),
        ];
    }

    protected function tearDown() {
        $connection = $this->db(static::createClient());
        $max = $this->maxIds;
        foreach ([
            "DELETE FROM questlog_deck WHERE id > {$max['questlog_deck']} OR questlog_id > {$max['questlog']}",
            "DELETE FROM questlog WHERE id > {$max['questlog']}",
            "DELETE FROM deckchange WHERE deck_id > {$max['deck']}",
            "DELETE FROM deckslot WHERE deck_id > {$max['deck']}",
            "DELETE FROM decksideslot WHERE deck_id > {$max['deck']}",
            "DELETE FROM deck WHERE id > {$max['deck']}",
        ] as $sql) {
            $connection->exec($sql);
        }
        if ($this->questlogOneState($connection) != $this->fixtureQuestlog) {
            list($questlog, $decks) = $this->fixtureQuestlog;
            $connection->update('questlog', $questlog, ['id' => 1]);
            $connection->exec('DELETE FROM questlog_deck WHERE questlog_id = 1');
            foreach ($decks as $row) {
                $connection->insert('questlog_deck', $row);
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
     * The cards of a deck, as the deck picker serializes them.
     */
    private function deckContent(Client $client, $deckId) {
        $rows = $this->db($client)->fetchAll('SELECT c.code, s.quantity FROM deckslot s JOIN card c ON c.id = s.card_id WHERE s.deck_id = ? ORDER BY c.code', [$deckId]);

        return json_encode(['main' => (object) array_map('intval', array_column($rows, 'quantity', 'code')), 'side' => new \stdClass()]);
    }

    /**
     * Fills the deck picker's hidden fields: [slot => [id, is_decklist, content]].
     */
    private static function selectDecks(Form $form, array $decks) {
        for ($i = 1; $i <= 4; $i++) {
            $form["deck{$i}_id"] = isset($decks[$i]) ? $decks[$i][0] : '';
            $form["deck{$i}_is_decklist"] = isset($decks[$i]) ? ($decks[$i][1] ? 'true' : 'false') : '';
            $form["questlogdeck{$i}_content"] = isset($decks[$i]) ? $decks[$i][2] : '';
        }
    }

    private function newForm(Client $client, $uri = '/questlog/new/0/0/0/0/0') {
        $crawler = $client->request('GET', $uri);
        $this->assertSame(200, $client->getResponse()->getStatusCode());

        return [$crawler, $crawler->filter('#save_form')->form()];
    }

    private function questlogIdFromRedirect(Client $client) {
        $this->assertSame(302, $client->getResponse()->getStatusCode());
        $location = $client->getResponse()->headers->get('Location');
        $this->assertRegExp('#^/questlog/view/\d+/#', $location);

        return (int) explode('/', $location)[3];
    }

    private function fetchQuestlog(Client $client, $id) {
        return $this->db($client)->fetchAssoc(
            'SELECT q.name, q.name_canonical, q.description_md, q.description_html, s.name AS scenario, q.date_played, q.quest_mode,
                    q.success, q.score, q.nb_decks, q.is_public, q.date_publish IS NOT NULL AS published, u.username
             FROM questlog q JOIN scenario s ON s.id = q.scenario_id JOIN user u ON u.id = q.user_id WHERE q.id = ?',
            [$id]
        );
    }

    private function fetchQuestlogDecks(Client $client, $id) {
        return $this->db($client)->fetchAll('SELECT deck_number, deck_id, decklist_id, player, content FROM questlog_deck WHERE questlog_id = ? ORDER BY deck_number', [$id]);
    }

    /* -------------------------------------------------------------- tests */

    public function testLogEditAndPublishAQuest() {
        $client = $this->createAuthenticatedClient();
        $deck1 = $this->deckContent($client, 1);
        $deck2 = $this->deckContent($client, 2);

        // 1. the form opened from 2 decks is prefilled with the player names
        list($crawler, $form) = $this->newForm($client, '/questlog/new/0/1/2/0/0');
        $this->assertSame('Log a Quest · RingsDB', trim($crawler->filter('title')->text()));
        $this->assertSame('', $form['questlog_id']->getValue());
        $this->assertSame('test', $form['questlogdeck1_player_name']->getValue());
        $this->assertSame('test', $form['questlogdeck2_player_name']->getValue());
        $this->assertSame('', $form['questlogdeck3_player_name']->getValue());
        $this->assertSame('yes', $form['victory']->getValue());
        $this->assertFalse($form['public']->hasValue());

        $form['quest'] = '2';
        $form['date'] = '2020-05-17';
        $form['difficulty'] = 'nightmare';
        $form['victory'] = 'no';
        $form['score'] = '142';
        $form['name'] = 'PHPUnit Quest';
        $form['descriptionMd'] = 'We *lost*';
        $form['questlogdeck1_player_name'] = 'Alice';
        $form['questlogdeck2_player_name'] = 'Bob';
        self::selectDecks($form, [1 => [1, false, $deck1], 2 => [2, false, $deck2]]);
        $client->submit($form);

        $id = $this->questlogIdFromRedirect($client);
        $this->assertSame("/questlog/view/$id/phpunitquest", $client->getResponse()->headers->get('Location'));
        $this->assertSame([
            'name' => 'PHPUnit Quest',
            'name_canonical' => 'phpunitquest',
            'description_md' => 'We *lost*',
            'description_html' => '<p>We <em>lost</em></p>',
            'scenario' => 'Journey Along the Anduin',
            'date_played' => '2020-05-17 00:00:00',
            'quest_mode' => 'nightmare',
            'success' => '0',
            'score' => '142',
            'nb_decks' => '2',
            'is_public' => '0',
            'published' => '0',
            'username' => 'test',
        ], $this->fetchQuestlog($client, $id));
        $this->assertSame([
            ['deck_number' => '1', 'deck_id' => '1', 'decklist_id' => null, 'player' => 'Alice', 'content' => $deck1],
            ['deck_number' => '2', 'deck_id' => '2', 'decklist_id' => null, 'player' => 'Bob', 'content' => $deck2],
        ], $this->fetchQuestlogDecks($client, $id));

        $crawler = $client->request('GET', "/questlog/view/$id/phpunitquest");
        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $this->assertSame('Journey Along the Anduin - Quest Log · RingsDB', trim($crawler->filter('title')->text()));

        // 2. edit: the form is prefilled; publish it, and log decklist 3 in slot 3 instead of deck 2
        list($crawler, $form) = $this->newForm($client, "/questlog/edit/$id");
        $this->assertSame('Edit Quest Log · RingsDB', trim($crawler->filter('title')->text()));
        $this->assertSame((string) $id, $form['questlog_id']->getValue());
        $this->assertSame('2', $form['quest']->getValue());
        $this->assertSame('2020-05-17', $form['date']->getValue());
        $this->assertSame('nightmare', $form['difficulty']->getValue());
        $this->assertSame('no', $form['victory']->getValue());
        $this->assertSame('142', $form['score']->getValue());
        $this->assertSame('PHPUnit Quest', $form['name']->getValue());
        $this->assertSame('We *lost*', $form['descriptionMd']->getValue());
        $this->assertSame('Alice', $form['questlogdeck1_player_name']->getValue());
        $this->assertSame($deck1, $form['questlogdeck1_content']->getValue());
        $this->assertSame($deck2, $form['questlogdeck2_content']->getValue());

        $deck3 = $this->deckContent($client, 3);
        $form['victory'] = 'yes';
        $form['public']->tick();
        $form['questlogdeck3_player_name'] = 'Carol';
        self::selectDecks($form, [1 => [1, false, $deck1], 3 => [3, true, $deck3]]);
        $client->submit($form);

        $this->assertSame($id, $this->questlogIdFromRedirect($client));
        $questlog = $this->fetchQuestlog($client, $id);
        $this->assertSame(['1', '2', '1', '1'], [$questlog['success'], $questlog['nb_decks'], $questlog['is_public'], $questlog['published']]);
        // a decklist is logged with its parent deck; slots are compacted
        $this->assertSame([
            ['deck_number' => '1', 'deck_id' => '1', 'decklist_id' => null, 'player' => 'Alice', 'content' => $deck1],
            ['deck_number' => '2', 'deck_id' => '3', 'decklist_id' => '3', 'player' => 'Carol', 'content' => $deck3],
        ], $this->fetchQuestlogDecks($client, $id));

        // 3. public quest logs are listed, and visible to other users
        $crawler = $client->request('GET', '/questlogs/recent');
        $this->assertContains('PHPUnit Quest', $crawler->filter('body')->text());
        $client = $this->createAuthenticatedClient('admin');
        $client->request('GET', "/questlog/view/$id/phpunitquest");
        $this->assertSame(200, $client->getResponse()->getStatusCode());
    }

    /**
     * The quest log keeps the cards that were posted, not the deck's current cards.
     */
    public function testLoggedContentIsKept() {
        $client = $this->createAuthenticatedClient();
        list(, $form) = $this->newForm($client);
        $form['quest'] = '1';
        $played = json_encode(['main' => ['01001' => 1, '01013' => 3], 'side' => new \stdClass()]);
        self::selectDecks($form, [1 => [1, false, $played]]);
        $client->submit($form);

        $id = $this->questlogIdFromRedirect($client);
        $this->assertSame($played, $this->fetchQuestlogDecks($client, $id)[0]['content']);
        $this->assertSame('Untitled Questlog', $this->fetchQuestlog($client, $id)['name']);
    }

    public function testInvalidValuesAreReplacedByDefaults() {
        $client = $this->createAuthenticatedClient();
        list(, $form) = $this->newForm($client);
        $values = $form->getValues();
        $values['quest'] = '1';
        $values['difficulty'] = 'legendary';
        $values['victory'] = 'maybe';
        $values['score'] = 'lots';
        $values['deck1_id'] = '1';
        $values['deck1_is_decklist'] = 'false';
        $values['questlogdeck1_content'] = $this->deckContent($client, 1);
        $client->request('POST', '/questlog/save', $values);

        $questlog = $this->fetchQuestlog($client, $this->questlogIdFromRedirect($client));
        $this->assertSame(['normal', '1', '0'], [$questlog['quest_mode'], $questlog['success'], $questlog['score']]);
    }

    /* ------------------------------------------------------------ refused */

    public function testQuestlogWithoutDeckIsRefused() {
        $client = $this->createAuthenticatedClient();
        list(, $form) = $this->newForm($client);
        $form['quest'] = '1';
        self::selectDecks($form, []);
        $client->submit($form);

        $this->assertSame(422, $client->getResponse()->getStatusCode());
        $this->assertSame('0', $this->db($client)->fetchColumn('SELECT COUNT(*) FROM questlog WHERE id > ?', [$this->maxIds['questlog']]));
    }

    public function testDeckWithoutContentIsRefused() {
        $client = $this->createAuthenticatedClient();
        list(, $form) = $this->newForm($client);
        $form['quest'] = '1';
        self::selectDecks($form, [1 => [1, false, '']]);
        $client->submit($form);

        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $this->assertSame('Cannot save a questlog with an empty deck', $client->getResponse()->getContent());
        $this->assertSame('0', $this->db($client)->fetchColumn('SELECT COUNT(*) FROM questlog WHERE id > ?', [$this->maxIds['questlog']]));
    }

    public function testUnknownScenarioIsRefused() {
        $client = $this->createAuthenticatedClient();
        list(, $form) = $this->newForm($client);
        $values = $form->getValues();
        $values['quest'] = '9999';
        $values['deck1_id'] = '1';
        $values['questlogdeck1_content'] = $this->deckContent($client, 1);
        $client->request('POST', '/questlog/save', $values);

        $this->assertSame(404, $client->getResponse()->getStatusCode());
    }

    /**
     * BUG: the branch meant for "the referenced deck was deleted" (deckN_id = 0 with a content)
     * reads "deckN_content", but the form posts "questlogdeckN_content": the slot is dropped.
     */
    public function testContentWithoutDeckIsDropped() {
        $client = $this->createAuthenticatedClient();
        list(, $form) = $this->newForm($client);
        $form['quest'] = '1';
        self::selectDecks($form, [1 => [1, false, $this->deckContent($client, 1)], 2 => [0, false, $this->deckContent($client, 2)]]);
        $client->submit($form);

        $id = $this->questlogIdFromRedirect($client);
        $this->assertCount(1, $this->fetchQuestlogDecks($client, $id));
        $this->assertSame('1', $this->fetchQuestlog($client, $id)['nb_decks']);
    }

    /* --------------------------------------------------- locked quest logs */

    public function testQuestlogWithSocialActivityKeepsItsDecks() {
        $client = $this->createAuthenticatedClient();
        $this->db($client)->update('questlog', ['nb_favorites' => 1], ['id' => 1]);
        $decks = $this->fetchQuestlogDecks($client, 1);

        list($crawler, $form) = $this->newForm($client, '/questlog/edit/1');
        $this->assertTrue($form['public']->isDisabled());
        $form['name'] = 'PHPUnit Renamed';
        self::selectDecks($form, [1 => [4, false, $this->deckContent($client, 4)]]);
        $client->submit($form);

        $this->assertSame(1, $this->questlogIdFromRedirect($client));
        $questlog = $this->fetchQuestlog($client, 1);
        $this->assertSame(['PHPUnit Renamed', '1', '4'], [$questlog['name'], $questlog['is_public'], $questlog['nb_decks']]);
        $this->assertSame($decks, $this->fetchQuestlogDecks($client, 1));
    }

    /* ---------------------------------------------------- other users */

    public function testUsingAnotherUsersDeckRequiresSharing() {
        $client = $this->createAuthenticatedClient('admin');
        list(, $form) = $this->newForm($client);
        $form['quest'] = '1';
        self::selectDecks($form, [1 => [1, false, $this->deckContent($client, 1)]]);

        $client->submit($form);
        $this->assertSame(403, $client->getResponse()->getStatusCode());

        // once shared, the deck is cloned for the admin; a published decklist needs no sharing
        $this->db($client)->update('user', ['is_share_decks' => 1], ['username' => 'test']);
        self::selectDecks($form, [1 => [1, false, $this->deckContent($client, 1)], 2 => [2, true, $this->deckContent($client, 2)]]);
        $client->submit($form);
        $id = $this->questlogIdFromRedirect($client);
        $decks = $this->fetchQuestlogDecks($client, $id);
        $this->assertGreaterThan($this->maxIds['deck'], (int) $decks[0]['deck_id']);
        $this->assertSame('admin', $this->db($client)->fetchColumn('SELECT u.username FROM deck d JOIN user u ON u.id = d.user_id WHERE d.id = ?', [$decks[0]['deck_id']]));
        $this->assertSame(['2', '2'], [$decks[1]['deck_id'], $decks[1]['decklist_id']]);
    }

    public function testPrivateQuestlogVisibility() {
        $client = $this->createAuthenticatedClient();
        list(, $form) = $this->newForm($client);
        $form['quest'] = '1';
        $form['name'] = 'PHPUnit Private';
        self::selectDecks($form, [1 => [1, false, $this->deckContent($client, 1)]]);
        $client->submit($form);
        $id = $this->questlogIdFromRedirect($client);

        $client = $this->createAuthenticatedClient('admin');
        $client->request('GET', "/questlog/view/$id/phpunitprivate");
        $this->assertSame(403, $client->getResponse()->getStatusCode());

        // visible once the owner shares their decks
        $this->db($client)->update('user', ['is_share_decks' => 1], ['username' => 'test']);
        $client->request('GET', "/questlog/view/$id/phpunitprivate");
        $this->assertSame(200, $client->getResponse()->getStatusCode());
    }

    public function testCannotChangeAnotherUsersQuestlog() {
        $client = $this->createAuthenticatedClient('admin');

        $client->request('GET', '/questlog/edit/1');
        $this->assertSame(403, $client->getResponse()->getStatusCode());

        $client->request('POST', '/questlog/save', ['questlog_id' => 1, 'quest' => 1, 'name' => 'Hacked']);
        $this->assertSame(403, $client->getResponse()->getStatusCode());

        $client->request('POST', '/questlog/delete', ['questlog_id' => 1]);
        $this->assertSame(403, $client->getResponse()->getStatusCode());

        $this->assertSame('Untitled Questlog', $this->fetchQuestlog($client, 1)['name']);
    }

    /* ------------------------------------------------------------- delete */

    public function testDeleteQuestlog() {
        $client = $this->createAuthenticatedClient();
        list(, $form) = $this->newForm($client);
        $form['quest'] = '1';
        self::selectDecks($form, [1 => [1, false, $this->deckContent($client, 1)]]);
        $client->submit($form);
        $id = $this->questlogIdFromRedirect($client);

        $client->request('POST', '/questlog/delete', ['questlog_id' => $id]);

        $this->assertSame(302, $client->getResponse()->getStatusCode());
        $this->assertSame('/myquestlogs', $client->getResponse()->headers->get('Location'));
        $this->assertFalse($this->fetchQuestlog($client, $id));
        $this->assertSame([], $this->fetchQuestlogDecks($client, $id));
        $this->assertSame('1', $this->db($client)->fetchColumn('SELECT COUNT(*) FROM deck WHERE id = 1'));
    }

    public function testQuestlogWithSocialActivityCannotBeDeleted() {
        $client = $this->createAuthenticatedClient();
        $this->db($client)->update('questlog', ['nb_votes' => 1], ['id' => 1]);

        $client->request('POST', '/questlog/delete', ['questlog_id' => 1]);

        $this->assertSame('/myquestlogs', $client->getResponse()->headers->get('Location'));
        $crawler = $client->followRedirect();
        $this->assertContains("You can't delete a published quest log.", $crawler->filter('body')->text());
        $this->assertSame('Untitled Questlog', $this->fetchQuestlog($client, 1)['name']);
    }

    public function testDeleteList() {
        $client = $this->createAuthenticatedClient();
        $ids = [];
        foreach ([1, 2] as $deckId) {
            list(, $form) = $this->newForm($client);
            $form['quest'] = '1';
            self::selectDecks($form, [1 => [$deckId, false, $this->deckContent($client, $deckId)]]);
            $client->submit($form);
            $ids[] = $this->questlogIdFromRedirect($client);
        }
        $this->db($client)->update('questlog', ['nb_comments' => 1], ['id' => $ids[1]]);

        $client->request('POST', '/questlog/delete_list', ['ids' => "{$ids[0]}-{$ids[1]}-999"]);

        $this->assertSame('/myquestlogs', $client->getResponse()->headers->get('Location'));
        $this->assertFalse($this->fetchQuestlog($client, $ids[0]));
        $this->assertNotFalse($this->fetchQuestlog($client, $ids[1]));
        $crawler = $client->followRedirect();
        $this->assertContains("You can't delete a published quest log. Unpublished selected quest logs were deleted.", $crawler->filter('body')->text());
    }
}
