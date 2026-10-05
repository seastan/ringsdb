<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Favorites and votes ("likes") on decklists (SocialController::favoriteAction and voteAction,
 * POST /user/favorite and /user/like, posted with AJAX by ui.decklist.js with the decklist
 * "id"). Both answer the new count as plain text.
 *
 * Fixture decklists 1-4 belong to "test" (reputation 1); "admin" (reputation 1) is the other user.
 * Counters, dates and reputations are restored in tearDown().
 */
class DecklistSocialTest extends WebTestCase
{
    private KernelBrowser $client;

    private array $fixtureDecklists;

    private array $fixtureUsers;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $connection = $this->db();
        $this->fixtureDecklists = $connection->fetchAll('SELECT id, nb_votes, nb_favorites, date_update FROM decklist');
        $this->fixtureUsers = $connection->fetchAll('SELECT id, reputation FROM user');
    }

    protected function tearDown(): void
    {
        $connection = $this->db();
        $connection->exec('DELETE FROM favorite');
        $connection->exec('DELETE FROM vote');
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

    private function createAuthenticatedClient(string $username): KernelBrowser
    {
        $client = $this->client;
        $crawler = $client->request('GET', '/login');
        $client->submit($crawler->selectButton('_submit')->form(['_username' => $username, '_password' => $username]));
        $this->assertTrue($client->getResponse()->isRedirect(), "Login as $username failed");

        return $client;
    }

    private function post(KernelBrowser $client, string $action, int $decklistId): \Symfony\Component\HttpFoundation\Response
    {
        $client->request('POST', "/user/$action", ['id' => $decklistId], [], ['HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest']);

        return $client->getResponse();
    }

    /**
     * @return array<string, mixed>
     */
    private function state(): array
    {
        $connection = $this->db();

        return [
            'nb_favorites' => $connection->fetchColumn('SELECT nb_favorites FROM decklist WHERE id = 2'),
            'nb_votes' => $connection->fetchColumn('SELECT nb_votes FROM decklist WHERE id = 2'),
            'favorites' => $connection->fetchColumn('SELECT COUNT(*) FROM favorite WHERE decklist_id = 2'),
            'votes' => $connection->fetchColumn('SELECT COUNT(*) FROM vote WHERE decklist_id = 2'),
            'author_reputation' => $connection->fetchColumn("SELECT reputation FROM user WHERE username = 'test'"),
        ];
    }

    /* ----------------------------------------------------------- favorite */

    /**
     * Favorite is a toggle; the author gains (then loses) 5 reputation points.
     */
    public function testFavoriteAndUnfavorite(): void
    {
        $client = $this->createAuthenticatedClient('admin');

        $response = $this->post($client, 'favorite', 2);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('1', $response->getContent());
        $this->assertSame(['nb_favorites' => '1', 'nb_votes' => '0', 'favorites' => '1', 'votes' => '0', 'author_reputation' => '6'], $this->state());
        $this->assertGreaterThan('2015-08-16 00:00:00', $this->db()->fetchColumn('SELECT date_update FROM decklist WHERE id = 2'));

        // the decklist is listed in the user's favorites
        $crawler = $client->request('GET', '/decklists/favorites');
        $this->assertContains('Gondor/Dunedain Leadership/Spirit', $crawler->filter('body')->text());

        $response = $this->post($client, 'favorite', 2);
        $this->assertSame('0', $response->getContent());
        $this->assertSame(['nb_favorites' => '0', 'nb_votes' => '0', 'favorites' => '0', 'votes' => '0', 'author_reputation' => '1'], $this->state());
    }

    public function testFavoriteOwnDecklistGivesNoReputation(): void
    {
        $client = $this->createAuthenticatedClient('test');

        $this->assertSame('1', $this->post($client, 'favorite', 2)->getContent());
        $this->assertSame(['nb_favorites' => '1', 'nb_votes' => '0', 'favorites' => '1', 'votes' => '0', 'author_reputation' => '1'], $this->state());
    }

    public function testFavoriteAnUnknownDecklist(): void
    {
        $client = $this->createAuthenticatedClient('admin');

        $response = $this->post($client, 'favorite', 999);

        // BUG (CoreExceptionListener): the 404 becomes a 500 for AJAX requests
        $this->assertSame(500, $response->getStatusCode());
        $this->assertSame(['success' => false, 'message' => 'Wrong id'], json_decode($response->getContent(), true));
    }

    /* --------------------------------------------------------------- vote */

    /**
     * A vote cannot be taken back; voting twice does nothing; the author gains 1 reputation point.
     */
    public function testVote(): void
    {
        $client = $this->createAuthenticatedClient('admin');

        $response = $this->post($client, 'like', 2);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('1', $response->getContent());
        $this->assertSame(['nb_favorites' => '0', 'nb_votes' => '1', 'favorites' => '0', 'votes' => '1', 'author_reputation' => '2'], $this->state());
        $this->assertGreaterThan('2015-08-16 00:00:00', $this->db()->fetchColumn('SELECT date_update FROM decklist WHERE id = 2'));

        $this->assertSame('1', $this->post($client, 'like', 2)->getContent());
        $this->assertSame(['nb_favorites' => '0', 'nb_votes' => '1', 'favorites' => '0', 'votes' => '1', 'author_reputation' => '2'], $this->state());
    }

    public function testCannotVoteForOwnDecklist(): void
    {
        $client = $this->createAuthenticatedClient('test');

        $this->assertSame('0', $this->post($client, 'like', 2)->getContent());
        $this->assertSame(['nb_favorites' => '0', 'nb_votes' => '0', 'favorites' => '0', 'votes' => '0', 'author_reputation' => '1'], $this->state());
    }

    public function testVoteForAnUnknownDecklist(): void
    {
        $client = $this->createAuthenticatedClient('admin');

        $client->request('POST', '/user/like', ['id' => 999]);
        $this->assertSame(400, $client->getResponse()->getStatusCode());

        // BUG (CoreExceptionListener): the 400 becomes a 500 for AJAX requests, as the page sends them
        $response = $this->post($client, 'like', 999);
        $this->assertSame(500, $response->getStatusCode());
        $this->assertSame(['success' => false, 'message' => 'Unable to find deck'], json_decode($response->getContent(), true));
    }

    /* ------------------------------------------------------------ access */

    /**
     * @dataProvider actionProvider
     */
    public function testAnonymousAjaxIsDenied(string $action): void
    {
        $client = $this->client;
        $response = $this->post($client, $action, 2);

        $this->assertSame(403, $response->getStatusCode());
        $this->assertSame(['success' => false, 'message' => 'Access Denied.'], json_decode($response->getContent(), true));
        $this->assertSame(['nb_favorites' => '0', 'nb_votes' => '0', 'favorites' => '0', 'votes' => '0', 'author_reputation' => '1'], $this->state());
    }

    public function actionProvider(): array
    {
        return ['favorite' => ['favorite'], 'vote' => ['like']];
    }

    /**
     * @dataProvider actionProvider
     */
    public function testGetIsNotAllowed(string $action): void
    {
        $client = $this->createAuthenticatedClient('admin');
        $client->request('GET', "/user/$action?id=2");

        $this->assertSame(405, $client->getResponse()->getStatusCode());
    }
}
