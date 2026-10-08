<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Editing and deleting a published decklist (SocialController): edit form
 * (GET /decklist/edit/{id}), save (POST /decklist/save/{id}), delete (POST /decklist/delete/{id}).
 *
 * /decklist/ has no access_control rule: the controllers check the user themselves.
 * Fixture decklists 1-4 belong to "test"; decklist 1 has a comment. Deletions are made on
 * decklists inserted by the test; everything is restored in tearDown().
 */
class DecklistEditTest extends WebTestCase
{
    use \App\Tests\FormFieldTrait;

    private KernelBrowser $client;

    private array $fixtureDecklists;

    private array $fixtureUsers;

    /** @var int[] */
    private array $maxIds = [];

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $connection = $this->db();
        $this->fixtureDecklists = $connection->fetchAllAssociative('SELECT id, name, name_canonical, description_md, description_html, precedent_decklist_id, date_update FROM decklist');
        $this->fixtureUsers = $connection->fetchAllAssociative('SELECT id, roles FROM user');
        foreach (['decklist', 'deck', 'fellowship'] as $table) {
            $this->maxIds[$table] = (int) $connection->fetchOne("SELECT MAX(id) FROM $table");
        }
    }

    protected function tearDown(): void
    {
        $connection = $this->db();
        $max = $this->maxIds;
        foreach ([
            "DELETE FROM fellowship_decklist WHERE fellowship_id > {$max['fellowship']}",
            "DELETE FROM fellowship WHERE id > {$max['fellowship']}",
            "DELETE FROM deckslot WHERE deck_id > {$max['deck']}",
            "DELETE FROM deck WHERE id > {$max['deck']}",
            "UPDATE decklist SET precedent_decklist_id = NULL WHERE id > {$max['decklist']}",
            "DELETE FROM decklist_spheres WHERE decklist_id > {$max['decklist']}",
            "DELETE FROM decklistslot WHERE decklist_id > {$max['decklist']}",
            "DELETE FROM decklist WHERE id > {$max['decklist']}",
        ] as $sql) {
            $connection->executeStatement($sql);
        }

        foreach ($this->fixtureDecklists as $decklist) {
            $connection->update('decklist', $decklist, ['id' => $decklist['id']]);
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

    private function fetchDecklist(int $id)
    {
        return $this->db()->fetchAssociative('SELECT name, name_canonical, description_md, description_html, precedent_decklist_id, date_update FROM decklist WHERE id = ?', [$id]);
    }

    /**
     * A copy of fixture decklist 2 (with its cards), returns its id.
     *
     * @param array<string, int> $values
     */
    private function insertDecklist(string $name, array $values = []): int
    {
        $connection = $this->db();
        $row = $connection->fetchAssociative('SELECT * FROM decklist WHERE id = 2');
        $this->assertNotFalse($row);
        unset($row['id']);
        $connection->insert('decklist', $values + ['name' => $name] + $row);
        $id = (int) $connection->lastInsertId();
        $connection->executeStatement("INSERT INTO decklistslot (decklist_id, card_id, quantity) SELECT $id, card_id, quantity FROM decklistslot WHERE decklist_id = 2");

        return $id;
    }

    /**
     * @param array<string, string> $values
     */
    private function saveForm(KernelBrowser $client, int $decklistId, array $values): \Symfony\Component\HttpFoundation\Response
    {
        $crawler = $client->request('GET', "/decklist/edit/$decklistId");
        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $client->submit($crawler->filter("form[action=\"/decklist/save/$decklistId\"]")->form($values));

        return $client->getResponse();
    }

    /* --------------------------------------------------------------- edit */

    public function testEditFormIsPrefilled(): void
    {
        $client = $this->createAuthenticatedClient();
        $this->db()->update('decklist', ['precedent_decklist_id' => 3], ['id' => 1]);
        $crawler = $client->request('GET', '/decklist/edit/1');

        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $form = $crawler->filter('form[action="/decklist/save/1"]')->form();
        $this->assertSame('Dwarf Lore/Leadership/Tactics', self::field($form, 'name')->getValue());
        $this->assertSame('Hello World', self::field($form, 'descriptionMd')->getValue());
        $this->assertSame('3', self::field($form, 'precedent')->getValue());
        // no deck: this is not the publish form
        $this->assertSame('', self::field($form, 'deck_id')->getValue());
    }

    public function testSave(): void
    {
        $client = $this->createAuthenticatedClient();

        $response = $this->saveForm($client, 1, ['name' => 'PHPUnit Renamed', 'descriptionMd' => 'Now **bold**', 'precedent' => '2']);

        // the canonical name keeps the version
        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame('/decklist/view/1/phpunitrenamed-1.0', $response->headers->get('Location'));
        $decklist = $this->fetchDecklist(1);
        $this->assertSame(
            ['PHPUnit Renamed', 'phpunitrenamed-1.0', 'Now **bold**', '<p>Now <strong>bold</strong></p>', 2],
            [$decklist['name'], $decklist['name_canonical'], $decklist['description_md'], $decklist['description_html'], $decklist['precedent_decklist_id']]
        );
        $this->assertGreaterThan('2015-08-16 00:00:00', $decklist['date_update']);

        $crawler = $client->followRedirect();
        $this->assertSame('PHPUnit Renamed · RingsDB', trim($crawler->filter('title')->text()));
    }

    #[DataProvider('nameProvider')]
    public function testName(string $name, string $expected): void
    {
        $client = $this->createAuthenticatedClient();
        $this->saveForm($client, 1, ['name' => $name]);

        $this->assertSame($expected, $this->fetchDecklist(1)['name']);
    }

    /**
     * @return array<string, string[]>
     */
    public static function nameProvider(): array
    {
        return [
            'empty' => ['  ', 'Untitled'],
            'tags are stripped' => ['<b>Bold</b> name', 'Bold name'],
            'at most 60 characters' => [str_repeat('x', 70), str_repeat('x', 60)],
        ];
    }

    /**
     * The predecessor ("Derived from") is given as an id or as a decklist URL.
     */
    #[DataProvider('precedentProvider')]
    public function testPrecedent(string $precedent, ?int $expected): void
    {
        $client = $this->createAuthenticatedClient();
        $this->saveForm($client, 1, ['precedent' => $precedent]);

        $this->assertSame($expected, $this->fetchDecklist(1)['precedent_decklist_id']);
    }

    /**
     * @return array<string, string[]|string[]|null[]>
     */
    public static function precedentProvider(): array
    {
        return [
            'id' => ['3', 3],
            'decklist URL' => ['https://ringsdb.com/decklist/view/4/gondorrohansilvantactics-1.0', 4],
            'empty' => ['', null],
            'itself' => ['1', null],
            'unknown decklist' => ['999', null],
            'anything else' => ['my favourite deck', null],
        ];
    }

    /* ------------------------------------------------------ access to edit */
    #[DataProvider('editRouteProvider')]
    public function testAnotherUserCannotEdit(string $method, string $uri): void
    {
        $client = $this->createAuthenticatedClient('admin');
        $client->request($method, $uri, ['name' => 'Hacked']);

        $this->assertSame(403, $client->getResponse()->getStatusCode());
        $this->assertSame('Dwarf Lore/Leadership/Tactics', $this->fetchDecklist(1)['name']);
    }

    /**
     * @return array<string, string[]>
     */
    public static function editRouteProvider(): array
    {
        return [
            'edit form' => ['GET', '/decklist/edit/1'],
            'save' => ['POST', '/decklist/save/1'],
        ];
    }

    /**
     * ROLE_SUPER_ADMIN can edit any decklist (not ROLE_ADMIN, see above).
     */
    public function testSuperAdminCanEdit(): void
    {
        $client = $this->client;
        $this->db()->update('user', ['roles' => serialize(['ROLE_SUPER_ADMIN'])], ['username' => 'admin']);
        $client = $this->createAuthenticatedClient('admin');

        $response = $this->saveForm($client, 1, ['name' => 'Moderated']);

        $this->assertSame('/decklist/view/1/moderated-1.0', $response->headers->get('Location'));
        $this->assertSame('Moderated', $this->fetchDecklist(1)['name']);
    }

    #[DataProvider('editRouteProvider')]
    public function testAnonymousIsRedirectedToLogin(string $method, string $uri): void
    {
        $client = $this->client;
        $client->request($method, $uri, ['name' => 'Hacked']);

        $this->assertSame(302, $client->getResponse()->getStatusCode());
        $this->assertSame('http://localhost/login', $client->getResponse()->headers->get('Location'));
        $this->assertSame('Dwarf Lore/Leadership/Tactics', $this->fetchDecklist(1)['name']);
    }

    public function testEditAnUnknownDecklist(): void
    {
        $client = $this->createAuthenticatedClient();
        $client->request('GET', '/decklist/edit/999');

        $this->assertSame(404, $client->getResponse()->getStatusCode());
    }

    /* ------------------------------------------------------------- delete */

    /**
     * The decks copied from the decklist and its successors are attached to its predecessor.
     */
    public function testDelete(): void
    {
        $client = $this->createAuthenticatedClient();
        $id = $this->insertDecklist('PHPUnit To Delete', ['precedent_decklist_id' => 1]);
        $successor = $this->insertDecklist('PHPUnit Successor', ['precedent_decklist_id' => $id]);
        $deck = $this->db()->fetchAssociative('SELECT * FROM deck WHERE id = 3');
        $this->assertNotFalse($deck);
        unset($deck['id']);
        $this->db()->insert('deck', ['name' => 'PHPUnit Child', 'parent_decklist_id' => $id] + $deck);
        $child = (int) $this->db()->lastInsertId();

        $client->request('POST', "/decklist/delete/$id");

        $this->assertSame(302, $client->getResponse()->getStatusCode());
        $this->assertSame('/decklists/mine', $client->getResponse()->headers->get('Location'));
        $this->assertFalse($this->fetchDecklist($id));
        $this->assertSame(0, $this->db()->fetchOne('SELECT COUNT(*) FROM decklistslot WHERE decklist_id = ?', [$id]));
        $this->assertSame(1, $this->fetchDecklist($successor)['precedent_decklist_id']);
        $this->assertSame(1, $this->db()->fetchOne('SELECT parent_decklist_id FROM deck WHERE id = ?', [$child]));
    }

    /**
     * Refusals are 403 (AccessDeniedHttpException): anonymous users are not redirected to the
     * login page, unlike on the edit routes. An unknown decklist is a 404.
     */
    #[DataProvider('refusedDeleteProvider')]
    public function testRefusedDelete(?string $username, int $decklist, int $status): void
    {
        $client = $username ? $this->createAuthenticatedClient($username) : $this->client;
        $client->request('POST', "/decklist/delete/$decklist");

        $this->assertSame($status, $client->getResponse()->getStatusCode());
        $this->assertSame(4, $this->db()->fetchOne('SELECT COUNT(*) FROM decklist'));
    }

    /**
     * @return array<string, array{?string, int, int}>
     */
    public static function refusedDeleteProvider(): array
    {
        return [
            'with a comment' => ['test', 1, 403],
            'of another user' => ['admin', 2, 403],
            'unknown' => ['test', 999, 404],
            'anonymous' => [null, 2, 403],
        ];
    }

    /**
     * BUG-ish: deleting a decklist used in a fellowship silently removes it from the fellowship
     * (cascade remove on Decklist.fellowships): the fellowship stays published, with one deck
     * less but the same nb_decks.
     */
    public function testDecklistUsedInAFellowship(): void
    {
        $client = $this->createAuthenticatedClient();
        $id = $this->insertDecklist('PHPUnit In A Fellowship');
        $connection = $this->db();
        $connection->insert('fellowship', ['user_id' => 1, 'name' => 'PHPUnit', 'name_canonical' => 'phpunit', 'is_public' => 1,
            'nb_decks' => 1, 'nb_votes' => 0, 'nb_favorites' => 0, 'nb_comments' => 0,
            'date_creation' => '2015-08-16 00:00:00', 'date_update' => '2015-08-16 00:00:00']);
        $fellowshipId = (int) $connection->lastInsertId();
        $connection->insert('fellowship_decklist', ['fellowship_id' => $fellowshipId, 'decklist_id' => $id, 'deck_number' => 1]);

        $client->request('POST', "/decklist/delete/$id");

        $this->assertSame(302, $client->getResponse()->getStatusCode());
        $this->assertFalse($this->fetchDecklist($id));
        $this->assertSame([1, 1], [
            $connection->fetchOne('SELECT is_public FROM fellowship WHERE id = ?', [$fellowshipId]),
            $connection->fetchOne('SELECT nb_decks FROM fellowship WHERE id = ?', [$fellowshipId]),
        ]);
        $this->assertSame(0, $connection->fetchOne('SELECT COUNT(*) FROM fellowship_decklist WHERE fellowship_id = ?', [$fellowshipId]));
    }
}
