<?php

namespace AppBundle\Tests\Model;

use AppBundle\Model\FellowshipManager;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;

/**
 * The find*() methods of FellowshipManager (lists and search of public fellowships).
 *
 * Data: fixture fellowship 1 (public, test, decks 1-4 but no decklist, published 2015-08-16,
 * no vote) plus, inserted by the test:
 * - F2 "PHPUnit Dwarves and Gondor": test, decklists 1 and 2, 12 votes, published 2020-01-01,
 *   3 comments (last 2020-02-01), favorite of admin;
 * - F3 "PHPUnit Noldor": admin, decklist 3, 2 votes, published 2021-01-01, 1 comment posted now;
 * - F4 "PHPUnit Private": test, not public (never listed), 50 votes;
 * - F5 "PHPUnit Aragorn": test, a decklist with a single Aragorn (Core Set), published
 *   2015-08-17.
 * Popularity is (1 + votes) / (1 + days since publication²): the order of these items does not
 * change over time.
 */
class FellowshipManagerTest extends KernelTestCase {
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
        foreach (['fellowship', 'fellowshipcomment', 'decklist'] as $table) {
            $this->maxIds[$table] = (int) $this->connection->fetchColumn("SELECT MAX(id) FROM $table");
        }
        $this->fixtureUsers = $this->connection->fetchAll('SELECT id, reputation FROM user');

        $admin = (int) $this->connection->fetchColumn("SELECT id FROM user WHERE username = 'admin'");
        $this->ids = ['F1' => 1];
        $this->ids['F2'] = $this->insertFellowship('PHPUnit Dwarves and Gondor', 1, [1, 2], ['nb_votes' => 12, 'nb_comments' => 3,
            'date_creation' => '2019-12-01 00:00:00', 'date_publish' => '2020-01-01 00:00:00', 'date_last_comment' => '2020-02-01 00:00:00']);
        $this->ids['F3'] = $this->insertFellowship('PHPUnit Noldor', $admin, [3], ['nb_votes' => 2, 'nb_comments' => 1,
            'date_creation' => '2020-12-01 00:00:00', 'date_publish' => '2021-01-01 00:00:00', 'date_last_comment' => '2021-03-01 00:00:00']);
        $this->ids['F4'] = $this->insertFellowship('PHPUnit Private', 1, [4], ['nb_votes' => 50, 'is_public' => 0]);
        $this->ids['F5'] = $this->insertFellowship('PHPUnit Aragorn', 1, [$this->insertDecklist([1 => 1])], [
            'date_creation' => '2015-08-17 00:00:00', 'date_publish' => '2015-08-17 00:00:00']);

        $this->connection->insert('fellowship_favorite', ['fellowship_id' => $this->ids['F2'], 'user_id' => $admin]);
        $this->connection->insert('fellowshipcomment', ['fellowship_id' => $this->ids['F3'], 'user_id' => 1, 'text' => 'Recent',
            'date_creation' => date('Y-m-d H:i:s'), 'is_hidden' => 0]);
    }

    protected function tearDown() {
        $max = $this->maxIds;
        foreach ([
            "DELETE FROM fellowshipcomment WHERE id > {$max['fellowshipcomment']}",
            "DELETE FROM fellowship_favorite WHERE fellowship_id > {$max['fellowship']}",
            "DELETE FROM fellowship_decklist WHERE fellowship_id > {$max['fellowship']}",
            "DELETE FROM fellowship WHERE id > {$max['fellowship']}",
            "DELETE FROM decklistslot WHERE decklist_id > {$max['decklist']}",
            "DELETE FROM decklist WHERE id > {$max['decklist']}",
        ] as $sql) {
            $this->connection->exec($sql);
        }
        foreach ($this->fixtureUsers as $user) {
            $this->connection->update('user', $user, ['id' => $user['id']]);
        }
        parent::tearDown();
    }

    /* ------------------------------------------------------------ helpers */

    private function insertFellowship($name, $userId, array $decklistIds, array $values) {
        $this->connection->insert('fellowship', $values + [
            'user_id' => $userId, 'name' => $name, 'name_canonical' => strtolower(str_replace(' ', '-', $name)),
            'is_public' => 1, 'nb_decks' => count($decklistIds), 'nb_votes' => 0, 'nb_favorites' => 0, 'nb_comments' => 0,
            'date_creation' => '2015-08-16 00:00:00', 'date_update' => '2015-08-16 00:00:00', 'date_publish' => '2015-08-16 00:00:00',
        ]);
        $id = (int) $this->connection->lastInsertId();
        foreach (array_values($decklistIds) as $i => $decklistId) {
            $this->connection->insert('fellowship_decklist', ['fellowship_id' => $id, 'decklist_id' => $decklistId, 'deck_number' => $i + 1]);
        }

        return $id;
    }

    /**
     * A copy of fixture decklist 2 with the given cards ([card id => quantity]).
     */
    private function insertDecklist(array $slots) {
        $row = $this->connection->fetchAssoc('SELECT * FROM decklist WHERE id = 2');
        unset($row['id']);
        $this->connection->insert('decklist', ['name' => 'PHPUnit Decklist'] + $row);
        $id = (int) $this->connection->lastInsertId();
        foreach ($slots as $cardId => $quantity) {
            $this->connection->insert('decklistslot', ['decklist_id' => $id, 'card_id' => $cardId, 'quantity' => $quantity]);
        }

        return $id;
    }

    /**
     * @return FellowshipManager
     */
    private function manager(array $query = [], $username = null) {
        $container = static::$kernel->getContainer();
        $container->get('request_stack')->push(Request::create('/fellowships/find', 'GET', $query));
        $manager = $container->get('fellowship_manager');
        if ($username) {
            $manager->setUser($container->get('doctrine')->getRepository('AppBundle:User')->findOneBy(['username' => $username]));
        }

        return $manager;
    }

    private function user($username) {
        return static::$kernel->getContainer()->get('doctrine')->getRepository('AppBundle:User')->findOneBy(['username' => $username]);
    }

    /**
     * @return string[] the names (F1...F5) of the fellowships found, in order
     */
    private function names($paginator) {
        $names = array_flip($this->ids);
        $result = [];
        foreach ($paginator as $fellowship) {
            $result[] = $names[$fellowship->getId()];
        }

        return $result;
    }

    /* -------------------------------------------------------------- lists */

    public function testLists() {
        $this->assertSame(['F2', 'F3', 'F5', 'F1'], $this->names($this->manager()->findFellowshipsByPopularity()));
        $this->assertSame(['F3', 'F2', 'F5', 'F1'], $this->names($this->manager()->findFellowshipsByAge()));
        $this->assertSame(['F3', 'F2'], $this->names($this->manager()->findFellowshipsByRecentDiscussion()));
        $this->assertSame(['F2'], $this->names($this->manager()->findFellowshipsByFavorite($this->user('admin'))));
        $this->assertSame([], $this->names($this->manager()->findFellowshipsByFavorite($this->user('test'))));
        // private fellowships are not listed, even for their author
        $this->assertSame(['F2', 'F5', 'F1'], $this->names($this->manager()->findFellowshipsByAuthor($this->user('test'))));
        // more than 10 votes
        $this->assertSame(['F2'], $this->names($this->manager()->findFellowshipsInHallOfFame()));
        // comments of the day first, then number of comments
        $this->assertSame(['F3', 'F2', 'F5', 'F1'], $this->names($this->manager()->findFellowshipsInHotTopic()));
    }

    public function testPagination() {
        $manager = $this->manager();
        $manager->setLimit(2);
        $manager->setPage(2);
        $list = $manager->findFellowshipsByAge();

        $this->assertSame(['F5', 'F1'], $this->names($list));
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
        $this->assertSame($expected, $this->names($this->manager($query, $username)->findFellowshipsWithComplexSearch()));
    }

    public function searchProvider() {
        return [
            'no criteria' => [[], ['F2', 'F3', 'F5', 'F1']],
            'author' => [['author' => 'admin'], ['F3']],
            'name' => [['name' => 'Noldor'], ['F3']],
            'number of decks' => [['nb_decks' => '2'], ['F2']],
            'card' => [['cards' => ['01004']], ['F2']],
            // the cards must be in the same decklist (Gimli is in decklist 1, Aragorn in decklist 2)
            'cards in different decklists' => [['cards' => ['01004', '01001']], []],
            // an unknown card is ignored, but only fellowships with decklists are searched
            'unknown card' => [['cards' => ['99999']], ['F2', 'F3', 'F5']],
            // one of the fellowship's decklists only uses cards of the given packs
            'packs' => [['packs' => ['1']], ['F5']],
            'packs, no match' => [['packs' => ['2']], []],
            // custom packs complete the packs, for the current user only
            'custom packs' => [['packs' => ['2'], 'custom_packs' => ['custom_test']], ['F5'], 'test'],
            'custom packs, anonymous' => [['packs' => ['2'], 'custom_packs' => ['custom_test']], []],
            'custom packs of another user' => [['packs' => ['2'], 'custom_packs' => ['custom_test']], [], 'admin'],
            // no more copies of a Core Set card than numcores Core Sets hold
            'number of core sets' => [['packs' => ['1'], 'numcores' => '1'], ['F5']],
            'number of core sets, none' => [['packs' => ['1'], 'numcores' => '0'], []],
            'sort by date' => [['sort' => 'date'], ['F3', 'F2', 'F5', 'F1']],
            'sort by likes' => [['sort' => 'likes'], ['F2', 'F3', 'F5', 'F1']],
        ];
    }

    public function testSortByReputation() {
        $this->connection->update('user', ['reputation' => 10], ['username' => 'admin']);

        $this->assertSame(['F3', 'F5', 'F2', 'F1'], $this->names($this->manager(['sort' => 'reputation'])->findFellowshipsWithComplexSearch()));
    }
}
