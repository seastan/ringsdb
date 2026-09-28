<?php

namespace AppBundle\Tests\Model;

use AppBundle\Model\QuestLogManager;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;

/**
 * The find*() methods of QuestLogManager (lists and search of public quest logs).
 *
 * Data: fixture quest log 1 (public, test, decks 1-4, published 2015-08-16, no vote) plus,
 * inserted by the test:
 * - Q2 "PHPUnit Dwarves and Gondor": test, decks 1 and 2, 12 votes, published 2020-01-01,
 *   3 comments, favorite of admin;
 * - Q3 "PHPUnit Noldor": admin, deck 3, 2 votes, published 2021-01-01, 1 comment posted now;
 * - Q4 "PHPUnit Private": test, not public (never listed), 50 votes;
 * - Q5 "PHPUnit Aragorn": test, a deck with a single Aragorn (Core Set), published 2015-08-17.
 * Unlike fellowships, the search by card or pack goes through the quest log's decks (not
 * decklists). Popularity is (1 + votes) / (1 + days since publication²): the order of these
 * items does not change over time.
 */
class QuestLogManagerTest extends KernelTestCase {
    /** @var Connection */
    private $connection;
    /** @var int[] */
    private $maxIds = [];
    /** @var int[] name => id */
    private $ids = [];
    /** @var array */
    private $fixtureUsers;

    protected function setUp() {
        static::bootKernel();
        $this->connection = static::$kernel->getContainer()->get('doctrine')->getConnection();
        foreach (['questlog', 'questlog_comment', 'deck'] as $table) {
            $this->maxIds[$table] = (int) $this->connection->fetchColumn("SELECT MAX(id) FROM $table");
        }
        $this->fixtureUsers = $this->connection->fetchAll('SELECT id, reputation FROM user');

        $admin = (int) $this->connection->fetchColumn("SELECT id FROM user WHERE username = 'admin'");
        $this->ids = ['Q1' => 1];
        $this->ids['Q2'] = $this->insertQuestlog('PHPUnit Dwarves and Gondor', 1, [1, 2], ['nb_votes' => 12, 'nb_comments' => 3,
            'date_creation' => '2019-12-01 00:00:00', 'date_publish' => '2020-01-01 00:00:00']);
        $this->ids['Q3'] = $this->insertQuestlog('PHPUnit Noldor', $admin, [3], ['nb_votes' => 2, 'nb_comments' => 1,
            'date_creation' => '2020-12-01 00:00:00', 'date_publish' => '2021-01-01 00:00:00']);
        $this->ids['Q4'] = $this->insertQuestlog('PHPUnit Private', 1, [4], ['nb_votes' => 50, 'is_public' => 0]);
        $this->ids['Q5'] = $this->insertQuestlog('PHPUnit Aragorn', 1, [$this->insertDeck([1 => 1])], [
            'date_creation' => '2015-08-17 00:00:00', 'date_publish' => '2015-08-17 00:00:00']);

        $this->connection->insert('questlog_favorite', ['questlog_id' => $this->ids['Q2'], 'user_id' => $admin]);
        $this->connection->insert('questlog_comment', ['questlog_id' => $this->ids['Q3'], 'user_id' => 1, 'text' => 'Recent',
            'date_creation' => date('Y-m-d H:i:s'), 'is_hidden' => 0]);
    }

    protected function tearDown() {
        $max = $this->maxIds;
        foreach ([
            "DELETE FROM questlog_comment WHERE id > {$max['questlog_comment']}",
            "DELETE FROM questlog_favorite WHERE questlog_id > {$max['questlog']}",
            "DELETE FROM questlog_deck WHERE questlog_id > {$max['questlog']}",
            "DELETE FROM questlog WHERE id > {$max['questlog']}",
            "DELETE FROM deckslot WHERE deck_id > {$max['deck']}",
            "DELETE FROM deck WHERE id > {$max['deck']}",
        ] as $sql) {
            $this->connection->exec($sql);
        }
        foreach ($this->fixtureUsers as $user) {
            $this->connection->update('user', $user, ['id' => $user['id']]);
        }
        parent::tearDown();
    }

    /* ------------------------------------------------------------ helpers */

    private function insertQuestlog($name, $userId, array $deckIds, array $values) {
        $this->connection->insert('questlog', $values + [
            'user_id' => $userId, 'scenario_id' => 1, 'name' => $name, 'name_canonical' => strtolower(str_replace(' ', '-', $name)),
            'date_played' => '2015-08-16 00:00:00', 'quest_mode' => 'normal', 'success' => 1, 'score' => 0,
            'is_public' => 1, 'nb_decks' => count($deckIds), 'nb_votes' => 0, 'nb_favorites' => 0, 'nb_comments' => 0,
            'date_creation' => '2015-08-16 00:00:00', 'date_update' => '2015-08-16 00:00:00', 'date_publish' => '2015-08-16 00:00:00',
        ]);
        $id = (int) $this->connection->lastInsertId();
        foreach (array_values($deckIds) as $i => $deckId) {
            $this->connection->insert('questlog_deck', ['questlog_id' => $id, 'deck_id' => $deckId, 'deck_number' => $i + 1,
                'player' => 'Player ' . ($i + 1), 'content' => '{"main":{},"side":[]}']);
        }

        return $id;
    }

    /**
     * A copy of fixture deck 2 with the given cards ([card id => quantity]).
     */
    private function insertDeck(array $slots) {
        $row = $this->connection->fetchAssoc('SELECT * FROM deck WHERE id = 2');
        unset($row['id']);
        $this->connection->insert('deck', ['name' => 'PHPUnit Deck'] + $row);
        $id = (int) $this->connection->lastInsertId();
        foreach ($slots as $cardId => $quantity) {
            $this->connection->insert('deckslot', ['deck_id' => $id, 'card_id' => $cardId, 'quantity' => $quantity]);
        }

        return $id;
    }

    /**
     * @return QuestLogManager
     */
    private function manager(array $query = [], $username = null) {
        $container = static::$kernel->getContainer();
        $container->get('request_stack')->push(Request::create('/questlogs/find', 'GET', $query));
        $manager = $container->get('questlog_manager');
        if ($username) {
            $manager->setUser($this->user($username));
        }

        return $manager;
    }

    private function user($username) {
        return static::$kernel->getContainer()->get('doctrine')->getRepository('AppBundle:User')->findOneBy(['username' => $username]);
    }

    /**
     * @return string[] the names (Q1...Q5) of the quest logs found, in order
     */
    private function names($paginator) {
        $names = array_flip($this->ids);
        $result = [];
        foreach ($paginator as $questlog) {
            $result[] = $names[$questlog->getId()];
        }

        return $result;
    }

    /* -------------------------------------------------------------- lists */

    public function testLists() {
        $this->assertSame(['Q2', 'Q3', 'Q5', 'Q1'], $this->names($this->manager()->findQuestLogsByPopularity()));
        $this->assertSame(['Q3', 'Q2', 'Q5', 'Q1'], $this->names($this->manager()->findQuestLogsByAge()));
        $this->assertSame(['Q2'], $this->names($this->manager()->findQuestLogsByFavorite($this->user('admin'))));
        $this->assertSame([], $this->names($this->manager()->findQuestLogsByFavorite($this->user('test'))));
        // private quest logs are not listed, even for their author
        $this->assertSame(['Q2', 'Q5', 'Q1'], $this->names($this->manager()->findQuestLogsByAuthor($this->user('test'))));
        // more than 10 votes
        $this->assertSame(['Q2'], $this->names($this->manager()->findQuestLogsInHallOfFame()));
        // comments of the day first, then number of comments
        $this->assertSame(['Q3', 'Q2', 'Q5', 'Q1'], $this->names($this->manager()->findQuestLogsInHotTopic()));
    }

    /**
     * Dead code: Questlog has no dateLastComment field (quest logs only count their comments), and
     * nothing calls this method.
     */
    public function testFindByRecentDiscussionIsBroken() {
        $this->expectException(\Doctrine\ORM\Query\QueryException::class);
        $this->expectExceptionMessage('has no field or association named dateLastComment');
        $this->manager()->findQuestLogsByRecentDiscussion();
    }

    public function testPagination() {
        $manager = $this->manager();
        $manager->setLimit(2);
        $manager->setPage(2);
        $list = $manager->findQuestLogsByAge();

        $this->assertSame(['Q5', 'Q1'], $this->names($list));
        $this->assertSame(4, $manager->getMaxCount());
        $this->assertSame(2, $manager->getNumberOfPages());
    }

    public function testEmptyList() {
        $manager = $this->manager();
        $this->assertCount(0, $manager->getEmptyList());
        $this->assertSame(0, $manager->getMaxCount());
    }

    /* ------------------------------------------------------------- search */

    /**
     * @dataProvider searchProvider
     */
    public function testComplexSearch(array $query, array $expected, $username = null) {
        $this->assertSame($expected, $this->names($this->manager($query, $username)->findQuestLogsWithComplexSearch()));
    }

    public function searchProvider() {
        return [
            'no criteria' => [[], ['Q2', 'Q3', 'Q5', 'Q1']],
            'author' => [['author' => 'admin'], ['Q3']],
            'name' => [['name' => 'Noldor'], ['Q3']],
            'number of decks' => [['nb_decks' => '2'], ['Q2']],
            // Gimli is in deck 1, used by Q2 and by the fixture quest log
            'card' => [['cards' => ['01004']], ['Q2', 'Q1']],
            // the cards must be in the same deck (Gimli is in deck 1, Aragorn in deck 2)
            'cards in different decks' => [['cards' => ['01004', '01001']], []],
            'unknown card' => [['cards' => ['99999']], ['Q2', 'Q3', 'Q5', 'Q1']],
            // one of the quest log's decks only uses cards of the given packs
            'packs' => [['packs' => ['1']], ['Q5']],
            'packs, no match' => [['packs' => ['2']], []],
            // custom packs complete the packs, for the current user only
            'custom packs' => [['packs' => ['2'], 'custom_packs' => ['custom_test']], ['Q5'], 'test'],
            'custom packs, anonymous' => [['packs' => ['2'], 'custom_packs' => ['custom_test']], []],
            'custom packs of another user' => [['packs' => ['2'], 'custom_packs' => ['custom_test']], [], 'admin'],
            'sort by date' => [['sort' => 'date'], ['Q3', 'Q2', 'Q5', 'Q1']],
            'sort by likes' => [['sort' => 'likes'], ['Q2', 'Q3', 'Q5', 'Q1']],
        ];
    }

    public function testSortByReputation() {
        $this->connection->update('user', ['reputation' => 10], ['username' => 'admin']);

        $this->assertSame(['Q3', 'Q5', 'Q2', 'Q1'], $this->names($this->manager(['sort' => 'reputation'])->findQuestLogsWithComplexSearch()));
    }
}
