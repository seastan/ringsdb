<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;
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
 */
class QuestlogWorkflowTest extends WebTestCase
{
    use \App\Tests\LocationTrait;
    use \App\Tests\FormFieldTrait;

    private KernelBrowser $client;

    /** @var int[] max ids before the test, by table */
    private array $maxIds = [];

    private array $fixtureQuestlog;

    private array $fixtureUsers;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $connection = $this->db();
        foreach (['questlog', 'questlog_deck', 'deck'] as $table) {
            $this->maxIds[$table] = (int) $connection->fetchOne("SELECT MAX(id) FROM $table");
        }

        $this->fixtureQuestlog = $this->questlogOneState($connection);
        $this->fixtureUsers = $connection->fetchAllAssociative('SELECT id, reputation FROM user');
    }

    /**
     * @return array<int, mixed>
     */
    private function questlogOneState(\Doctrine\DBAL\Connection $connection): array
    {
        return [
            $connection->fetchAssociative('SELECT * FROM questlog WHERE id = 1'),
            $connection->fetchAllAssociative('SELECT * FROM questlog_deck WHERE questlog_id = 1 ORDER BY id'),
        ];
    }

    protected function tearDown(): void
    {
        $connection = $this->db();
        $max = $this->maxIds;
        foreach ([
            'DELETE FROM questlog_favorite',
            "DELETE FROM questlog_deck WHERE id > {$max['questlog_deck']} OR questlog_id > {$max['questlog']}",
            "DELETE FROM questlog WHERE id > {$max['questlog']}",
            "DELETE FROM deckchange WHERE deck_id > {$max['deck']}",
            "DELETE FROM deckslot WHERE deck_id > {$max['deck']}",
            "DELETE FROM decksideslot WHERE deck_id > {$max['deck']}",
            "DELETE FROM deck WHERE id > {$max['deck']}",
        ] as $sql) {
            $connection->executeStatement($sql);
        }

        if ($this->questlogOneState($connection) != $this->fixtureQuestlog) {
            [$questlog, $decks] = $this->fixtureQuestlog;
            $connection->update('questlog', $questlog, ['id' => 1]);
            $connection->executeStatement('DELETE FROM questlog_deck WHERE questlog_id = 1');
            foreach ($decks as $row) {
                $connection->insert('questlog_deck', $row);
            }
        }

        foreach ($this->fixtureUsers as $user) {
            $connection->update('user', $user, ['id' => $user['id']]);
        }

        $connection->update('user', ['is_share_decks' => 0], ['username' => 'test']);
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
     * The cards of a deck, as the deck picker serializes them.
     */
    private function deckContent(int $deckId): string|false
    {
        $rows = $this->db()->fetchAllAssociative('SELECT c.code, s.quantity FROM deckslot s JOIN card c ON c.id = s.card_id WHERE s.deck_id = ? ORDER BY c.code', [$deckId]);

        return json_encode(['main' => array_map(intval(...), array_column($rows, 'quantity', 'code')), 'side' => []]);
    }

    /**
     * Fills the deck picker's hidden fields: [slot => [id, is_decklist, content]].
     *
     * @param array<int, mixed> $decks
     */
    private function selectDecks(Form $form, array $decks): void
    {
        for ($i = 1; $i <= 4; ++$i) {
            $form["deck{$i}_id"] = isset($decks[$i]) ? $decks[$i][0] : '';
            $form["deck{$i}_is_decklist"] = isset($decks[$i]) ? ($decks[$i][1] ? 'true' : 'false') : '';
            $form["questlogdeck{$i}_content"] = isset($decks[$i]) ? $decks[$i][2] : '';
        }
    }

    /**
     * @return array{\Symfony\Component\DomCrawler\Crawler, Form}
     */
    private function newForm(KernelBrowser $client, string $uri = '/questlog/new/0/0/0/0/0'): array
    {
        $crawler = $client->request('GET', $uri);
        $this->assertSame(200, $client->getResponse()->getStatusCode());

        return [$crawler, $crawler->filter('#save_form')->form()];
    }

    private function questlogIdFromRedirect(KernelBrowser $client): int
    {
        $this->assertSame(302, $client->getResponse()->getStatusCode());
        $location = self::location($client->getResponse());
        $this->assertMatchesRegularExpression('#^/questlog/view/\d+/#', $location);

        return (int) explode('/', $location)[3];
    }

    private function fetchQuestlog(int $id)
    {
        return $this->db()->fetchAssociative(
            'SELECT q.name, q.name_canonical, q.description_md, q.description_html, s.name AS scenario, q.date_played, q.quest_mode,
                    q.success, q.score, q.nb_decks, q.is_public, q.date_publish IS NOT NULL AS published, u.username
             FROM questlog q JOIN scenario s ON s.id = q.scenario_id JOIN user u ON u.id = q.user_id WHERE q.id = ?',
            [$id]
        );
    }

    private function fetchQuestlogDecks(int $id): array
    {
        return $this->db()->fetchAllAssociative('SELECT deck_number, deck_id, decklist_id, player, content FROM questlog_deck WHERE questlog_id = ? ORDER BY deck_number', [$id]);
    }

    /* -------------------------------------------------------------- tests */

    public function testLogEditAndPublishAQuest(): void
    {
        $client = $this->createAuthenticatedClient();
        $deck1 = $this->deckContent(1);
        $deck2 = $this->deckContent(2);

        // 1. the form opened from 2 decks is prefilled with the player names
        [$crawler, $form] = $this->newForm($client, '/questlog/new/0/1/2/0/0');
        $this->assertSame('Log a Quest · RingsDB', trim($crawler->filter('title')->text()));
        $this->assertSame('', self::field($form, 'questlog_id')->getValue());
        $this->assertSame('test', self::field($form, 'questlogdeck1_player_name')->getValue());
        $this->assertSame('test', self::field($form, 'questlogdeck2_player_name')->getValue());
        $this->assertSame('', self::field($form, 'questlogdeck3_player_name')->getValue());
        $this->assertSame('yes', self::field($form, 'victory')->getValue());
        $this->assertFalse(self::field($form, 'public')->hasValue());

        $form['quest'] = '2';
        $form['date'] = '2020-05-17';
        $form['difficulty'] = 'nightmare';
        $form['victory'] = 'no';
        $form['score'] = '142';
        $form['name'] = 'PHPUnit Quest';
        $form['descriptionMd'] = 'We *lost*';
        $form['questlogdeck1_player_name'] = 'Alice';
        $form['questlogdeck2_player_name'] = 'Bob';
        $this->selectDecks($form, [1 => [1, false, $deck1], 2 => [2, false, $deck2]]);
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
            'success' => 0,
            'score' => 142,
            'nb_decks' => 2,
            'is_public' => 0,
            'published' => 0,
            'username' => 'test',
        ], $this->fetchQuestlog($id));
        $this->assertSame([
            ['deck_number' => 1, 'deck_id' => 1, 'decklist_id' => null, 'player' => 'Alice', 'content' => $deck1],
            ['deck_number' => 2, 'deck_id' => 2, 'decklist_id' => null, 'player' => 'Bob', 'content' => $deck2],
        ], $this->fetchQuestlogDecks($id));

        $crawler = $client->request('GET', "/questlog/view/$id/phpunitquest");
        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $this->assertSame('Journey Along the Anduin - Quest Log · RingsDB', trim($crawler->filter('title')->text()));

        // 2. edit: the form is prefilled; publish it, and log decklist 3 in slot 3 instead of deck 2
        [$crawler, $form] = $this->newForm($client, "/questlog/edit/$id");
        $this->assertSame('Edit Quest Log · RingsDB', trim($crawler->filter('title')->text()));
        $this->assertSame((string) $id, self::field($form, 'questlog_id')->getValue());
        $this->assertSame('2', self::field($form, 'quest')->getValue());
        $this->assertSame('2020-05-17', self::field($form, 'date')->getValue());
        $this->assertSame('nightmare', self::field($form, 'difficulty')->getValue());
        $this->assertSame('no', self::field($form, 'victory')->getValue());
        $this->assertSame('142', self::field($form, 'score')->getValue());
        $this->assertSame('PHPUnit Quest', self::field($form, 'name')->getValue());
        $this->assertSame('We *lost*', self::field($form, 'descriptionMd')->getValue());
        $this->assertSame('Alice', self::field($form, 'questlogdeck1_player_name')->getValue());
        $this->assertSame($deck1, self::field($form, 'questlogdeck1_content')->getValue());
        $this->assertSame($deck2, self::field($form, 'questlogdeck2_content')->getValue());

        $deck3 = $this->deckContent(3);
        $form['victory'] = 'yes';
        /** @var \Symfony\Component\DomCrawler\Field\ChoiceFormField $public */
        $public = $form['public'];
        $public->tick();
        $form['questlogdeck3_player_name'] = 'Carol';
        $this->selectDecks($form, [1 => [1, false, $deck1], 3 => [3, true, $deck3]]);
        $client->submit($form);

        $this->assertSame($id, $this->questlogIdFromRedirect($client));
        $questlog = $this->fetchQuestlog($id);
        $this->assertSame([1, 2, 1, 1], [$questlog['success'], $questlog['nb_decks'], $questlog['is_public'], $questlog['published']]);
        // a decklist is logged with its parent deck; slots are compacted
        $this->assertSame([
            ['deck_number' => 1, 'deck_id' => 1, 'decklist_id' => null, 'player' => 'Alice', 'content' => $deck1],
            ['deck_number' => 2, 'deck_id' => 3, 'decklist_id' => 3, 'player' => 'Carol', 'content' => $deck3],
        ], $this->fetchQuestlogDecks($id));

        // 3. public quest logs are listed, and visible to other users
        $crawler = $client->request('GET', '/questlogs/recent');
        $this->assertStringContainsString('PHPUnit Quest', $crawler->filter('body')->text());
        $client = $this->createAuthenticatedClient('admin');
        $client->request('GET', "/questlog/view/$id/phpunitquest");
        $this->assertSame(200, $client->getResponse()->getStatusCode());
    }

    /**
     * Snapshots stored before the card-printings migration can use the code of a merged card:
     * 31031 is now a printing of card 17143 (Magic Ring). The lists build the snapshots.
     */
    public function testSnapshotWithTheCodeOfAMergedCard(): void
    {
        $client = $this->client;
        $content = (string) $this->db()->fetchOne('SELECT content FROM questlog_deck WHERE id = 1');
        try {
            $data = json_decode($content, true);
            $data['main']['31031'] = 1;
            $data['side']['99999'] = 1;
            $this->db()->update('questlog_deck', ['content' => json_encode($data)], ['id' => 1]);

            $client->request('GET', '/questlogs/recent');
            $this->assertSame(200, $client->getResponse()->getStatusCode());
        } finally {
            $this->db()->update('questlog_deck', ['content' => $content], ['id' => 1]);
        }
    }

    /**
     * The quest log keeps the cards that were posted, not the deck's current cards.
     */
    public function testLoggedContentIsKept(): void
    {
        $client = $this->createAuthenticatedClient();
        [, $form] = $this->newForm($client);
        $form['quest'] = '1';
        $played = json_encode(['main' => ['01001' => 1, '01013' => 3], 'side' => []]);
        $this->selectDecks($form, [1 => [1, false, $played]]);
        $client->submit($form);

        $id = $this->questlogIdFromRedirect($client);
        $this->assertSame($played, $this->fetchQuestlogDecks($id)[0]['content']);
        $this->assertSame('Untitled Questlog', $this->fetchQuestlog($id)['name']);
    }

    /**
     * The lists of quest logs rebuild their decks from the logged content (setSnapshot): a card of
     * the content missing from the database (e.g. a card not imported yet) is skipped, instead of
     * failing the page.
     */
    public function testUnknownCardOfTheLoggedContentIsSkipped(): void
    {
        $client = $this->createAuthenticatedClient();
        [, $form] = $this->newForm($client);
        $form['quest'] = '1';
        $played = json_encode(['main' => ['01001' => 1, '99999' => 2], 'side' => ['99998' => 1]]);
        $this->selectDecks($form, [1 => [1, false, $played]]);
        $client->submit($form);
        $this->questlogIdFromRedirect($client);

        $client->request('GET', '/myquestlogs');

        $this->assertSame(200, $client->getResponse()->getStatusCode());
    }

    public function testInvalidValuesAreReplacedByDefaults(): void
    {
        $client = $this->createAuthenticatedClient();
        [, $form] = $this->newForm($client);
        $values = $form->getValues();
        $values['quest'] = '1';
        $values['difficulty'] = 'legendary';
        $values['victory'] = 'maybe';
        $values['score'] = 'lots';
        $values['deck1_id'] = '1';
        $values['deck1_is_decklist'] = 'false';
        $values['questlogdeck1_content'] = $this->deckContent(1);
        $client->request('POST', '/questlog/save', $values);

        $questlog = $this->fetchQuestlog($this->questlogIdFromRedirect($client));
        $this->assertSame(['normal', 1, 0], [$questlog['quest_mode'], $questlog['success'], $questlog['score']]);
    }

    /* ------------------------------------------------------------ refused */

    public function testQuestlogWithoutDeckIsRefused(): void
    {
        $client = $this->createAuthenticatedClient();
        [, $form] = $this->newForm($client);
        $form['quest'] = '1';
        $this->selectDecks($form, []);
        $client->submit($form);

        $this->assertSame(422, $client->getResponse()->getStatusCode());
        $this->assertSame(0, $this->db()->fetchOne('SELECT COUNT(*) FROM questlog WHERE id > ?', [$this->maxIds['questlog']]));
    }

    public function testDeckWithoutContentIsRefused(): void
    {
        $client = $this->createAuthenticatedClient();
        [, $form] = $this->newForm($client);
        $form['quest'] = '1';
        $this->selectDecks($form, [1 => [1, false, '']]);
        $client->submit($form);

        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $this->assertSame('Cannot save a questlog with an empty deck', $client->getResponse()->getContent());
        $this->assertSame(0, $this->db()->fetchOne('SELECT COUNT(*) FROM questlog WHERE id > ?', [$this->maxIds['questlog']]));
    }

    public function testUnknownScenarioIsRefused(): void
    {
        $client = $this->createAuthenticatedClient();
        [, $form] = $this->newForm($client);
        $values = $form->getValues();
        $values['quest'] = '9999';
        $values['deck1_id'] = '1';
        $values['questlogdeck1_content'] = $this->deckContent(1);
        $client->request('POST', '/questlog/save', $values);

        $this->assertSame(404, $client->getResponse()->getStatusCode());
    }

    /**
     * BUG: the branch meant for "the referenced deck was deleted" (deckN_id = 0 with a content)
     * reads "deckN_content", but the form posts "questlogdeckN_content": the slot is dropped.
     */
    public function testContentWithoutDeckIsDropped(): void
    {
        $client = $this->createAuthenticatedClient();
        [, $form] = $this->newForm($client);
        $form['quest'] = '1';
        $this->selectDecks($form, [1 => [1, false, $this->deckContent(1)], 2 => [0, false, $this->deckContent(2)]]);
        $client->submit($form);

        $id = $this->questlogIdFromRedirect($client);
        $this->assertCount(1, $this->fetchQuestlogDecks($id));
        $this->assertSame(1, $this->fetchQuestlog($id)['nb_decks']);
    }

    /* --------------------------------------------------- locked quest logs */

    public function testQuestlogWithSocialActivityKeepsItsDecks(): void
    {
        $client = $this->createAuthenticatedClient();
        $this->db()->update('questlog', ['nb_favorites' => 1], ['id' => 1]);
        $decks = $this->fetchQuestlogDecks(1);

        [$crawler, $form] = $this->newForm($client, '/questlog/edit/1');
        $this->assertTrue(self::field($form, 'public')->isDisabled());
        $form['name'] = 'PHPUnit Renamed';
        $this->selectDecks($form, [1 => [4, false, $this->deckContent(4)]]);
        $client->submit($form);

        $this->assertSame(1, $this->questlogIdFromRedirect($client));
        $questlog = $this->fetchQuestlog(1);
        $this->assertSame(['PHPUnit Renamed', 1, 4], [$questlog['name'], $questlog['is_public'], $questlog['nb_decks']]);
        $this->assertSame($decks, $this->fetchQuestlogDecks(1));
    }

    /* ---------------------------------------------------- other users */

    public function testUsingAnotherUsersDeckRequiresSharing(): void
    {
        $client = $this->createAuthenticatedClient('admin');
        [, $form] = $this->newForm($client);
        $form['quest'] = '1';
        $this->selectDecks($form, [1 => [1, false, $this->deckContent(1)]]);

        $client->submit($form);
        $this->assertSame(403, $client->getResponse()->getStatusCode());

        // once shared, the deck is cloned for the admin; a published decklist needs no sharing
        $this->db()->update('user', ['is_share_decks' => 1], ['username' => 'test']);
        $this->selectDecks($form, [1 => [1, false, $this->deckContent(1)], 2 => [2, true, $this->deckContent(2)]]);
        $client->submit($form);
        $id = $this->questlogIdFromRedirect($client);
        $decks = $this->fetchQuestlogDecks($id);
        $this->assertGreaterThan($this->maxIds['deck'], (int) $decks[0]['deck_id']);
        $this->assertSame('admin', $this->db()->fetchOne('SELECT u.username FROM deck d JOIN user u ON u.id = d.user_id WHERE d.id = ?', [$decks[0]['deck_id']]));
        $this->assertSame([2, 2], [$decks[1]['deck_id'], $decks[1]['decklist_id']]);
    }

    public function testPrivateQuestlogVisibility(): void
    {
        $client = $this->createAuthenticatedClient();
        [, $form] = $this->newForm($client);
        $form['quest'] = '1';
        $form['name'] = 'PHPUnit Private';
        $this->selectDecks($form, [1 => [1, false, $this->deckContent(1)]]);
        $client->submit($form);
        $id = $this->questlogIdFromRedirect($client);

        $client = $this->createAuthenticatedClient('admin');
        $client->request('GET', "/questlog/view/$id/phpunitprivate");
        $this->assertSame(403, $client->getResponse()->getStatusCode());

        // visible once the owner shares their decks
        $this->db()->update('user', ['is_share_decks' => 1], ['username' => 'test']);
        $client->request('GET', "/questlog/view/$id/phpunitprivate");
        $this->assertSame(200, $client->getResponse()->getStatusCode());
    }

    public function testCannotChangeAnotherUsersQuestlog(): void
    {
        $client = $this->createAuthenticatedClient('admin');

        $client->request('GET', '/questlog/edit/1');
        $this->assertSame(403, $client->getResponse()->getStatusCode());

        $client->request('POST', '/questlog/save', ['questlog_id' => 1, 'quest' => 1, 'name' => 'Hacked']);
        $this->assertSame(403, $client->getResponse()->getStatusCode());

        $client->request('POST', '/questlog/delete', ['questlog_id' => 1]);
        $this->assertSame(403, $client->getResponse()->getStatusCode());

        $this->assertSame('Untitled Questlog', $this->fetchQuestlog(1)['name']);
    }

    /* ------------------------------------------------------------- delete */

    public function testDeleteQuestlog(): void
    {
        $client = $this->createAuthenticatedClient();
        [, $form] = $this->newForm($client);
        $form['quest'] = '1';
        $this->selectDecks($form, [1 => [1, false, $this->deckContent(1)]]);
        $client->submit($form);
        $id = $this->questlogIdFromRedirect($client);

        $client->request('POST', '/questlog/delete', ['questlog_id' => $id]);

        $this->assertSame(302, $client->getResponse()->getStatusCode());
        $this->assertSame('/myquestlogs', $client->getResponse()->headers->get('Location'));
        $this->assertFalse($this->fetchQuestlog($id));
        $this->assertSame([], $this->fetchQuestlogDecks($id));
        $this->assertSame(1, $this->db()->fetchOne('SELECT COUNT(*) FROM deck WHERE id = 1'));
    }

    public function testQuestlogWithSocialActivityCannotBeDeleted(): void
    {
        $client = $this->createAuthenticatedClient();
        $this->db()->update('questlog', ['nb_votes' => 1], ['id' => 1]);

        $client->request('POST', '/questlog/delete', ['questlog_id' => 1]);

        $this->assertSame('/myquestlogs', $client->getResponse()->headers->get('Location'));
        $crawler = $client->followRedirect();
        $this->assertStringContainsString("You can't delete a published quest log.", $crawler->filter('body')->text());
        $this->assertSame('Untitled Questlog', $this->fetchQuestlog(1)['name']);
    }

    public function testDeleteList(): void
    {
        $client = $this->createAuthenticatedClient();
        $ids = [];
        foreach ([1, 2] as $deckId) {
            [, $form] = $this->newForm($client);
            $form['quest'] = '1';
            $this->selectDecks($form, [1 => [$deckId, false, $this->deckContent($deckId)]]);
            $client->submit($form);
            $ids[] = $this->questlogIdFromRedirect($client);
        }

        $this->db()->update('questlog', ['nb_comments' => 1], ['id' => $ids[1]]);

        $client->request('POST', '/questlog/delete_list', ['ids' => "{$ids[0]}-{$ids[1]}-999"]);

        $this->assertSame('/myquestlogs', $client->getResponse()->headers->get('Location'));
        $this->assertFalse($this->fetchQuestlog($ids[0]));
        $this->assertNotFalse($this->fetchQuestlog($ids[1]));
        $crawler = $client->followRedirect();
        $this->assertStringContainsString("You can't delete a published quest log. Unpublished selected quest logs were deleted.", $crawler->filter('body')->text());
    }

    /**
     * Fixed: commenting on or voting for an unknown quest log crashed (500): 400.
     */
    public function testCommentAndVoteOnAnUnknownQuestLog(): void
    {
        $client = $this->createAuthenticatedClient();

        $client->request('POST', '/user/questlog_comment', ['id' => 999, 'comment' => 'Hello']);
        $this->assertSame(400, $client->getResponse()->getStatusCode());

        $client->request('POST', '/user/questlog_like', ['id' => 999]);
        $this->assertSame(400, $client->getResponse()->getStatusCode());
    }

    /**
     * Favorite is a toggle (POST /user/questlog_favorite, answers the new count as plain text);
     * the author gains (then loses) 5 reputation points.
     */
    public function testFavoriteAndUnfavorite(): void
    {
        $client = $this->createAuthenticatedClient('admin');
        $state = fn () => $this->db()->fetchAssociative(
            'SELECT q.nb_favorites, (SELECT COUNT(*) FROM questlog_favorite WHERE questlog_id = 1) AS favorites, u.reputation AS author_reputation
            FROM questlog q JOIN user u ON u.id = q.user_id WHERE q.id = 1'
        );

        $client->request('POST', '/user/questlog_favorite', ['id' => 1]);
        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $this->assertSame('1', $client->getResponse()->getContent());
        $this->assertSame(['nb_favorites' => 1, 'favorites' => 1, 'author_reputation' => 6], $state());
        $this->assertGreaterThan('2015-08-16 00:00:00', $this->db()->fetchOne('SELECT date_update FROM questlog WHERE id = 1'));

        $client->request('POST', '/user/questlog_favorite', ['id' => 1]);
        $this->assertSame('0', $client->getResponse()->getContent());
        $this->assertSame(['nb_favorites' => 0, 'favorites' => 0, 'author_reputation' => 1], $state());
    }
}
