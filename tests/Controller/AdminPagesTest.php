<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Admin area (/admin/*), read-only: access control, and pages as seen by the "admin" user
 * (ROLE_ADMIN).
 *
 * Pages are compared to text snapshots in Tests/Resources/snapshots/pages/admin/ (see
 * PageSnapshotTrait). The card and card printing lists (1300+ rows) are only checked by their
 * number of rows.
 */
class AdminPagesTest extends WebTestCase
{
    use PageSnapshotTrait;
    use JsonSnapshotTrait;

    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
    }

    /* ------------------------------------------------------------ helpers */

    private function createAuthenticatedClient($username): KernelBrowser
    {
        $client = $this->client;
        $crawler = $client->request('GET', '/login');
        $client->submit($crawler->selectButton('_submit')->form(['_username' => $username, '_password' => $username]));
        $this->assertTrue($client->getResponse()->isRedirect(), "Login as $username failed");

        return $client;
    }

    private function db(): \Doctrine\DBAL\Connection
    {
        return static::getContainer()->get('doctrine')->getConnection();
    }

    /**
     * GET pages of the admin area: [uri, snapshot name or null].
     */
    public function adminPageProvider(): array
    {
        $pages = [
            'home' => ['/admin/', 'index'],
            // JSON, see testStatistics
            'statistics' => ['/admin/stat?month=2015-08', null],
            'pack statistics' => ['/admin/stat_packs?month=2015-08', null],
            'command form' => ['/admin/command/', 'command'],
            'excel download form' => ['/admin/excel/download', 'excel_download'],
            'excel upload form' => ['/admin/excel/upload', 'excel_upload'],
            'csv upload form' => ['/admin/csv/upload', 'csv_upload'],
            'find user form' => ['/admin/user/find', 'user_find'],
            // the last update date changes at each login, see testUserPage
            'user' => ['/admin/user/show/1', null],
            'user decklists' => ['/admin/user/decklists/1', 'user_decklists_1'],
            'user comments' => ['/admin/user/comments/1', 'user_comments_1'],
        ];
        foreach (['cycle', 'pack', 'type', 'sphere', 'card', 'card-printing', 'scenario', 'encounter'] as $entity) {
            $big = in_array($entity, ['card', 'card-printing'], true);
            $pages["$entity list"] = ["/admin/$entity/", $big ? null : "{$entity}_list"];
            $pages["$entity show"] = ["/admin/$entity/1/show", "{$entity}_show_1"];
            $pages["$entity new form"] = ["/admin/$entity/new", "{$entity}_new"];
            $pages["$entity edit form"] = ["/admin/$entity/1/edit", "{$entity}_edit_1"];
        }

        return $pages;
    }

    /* ----------------------------------------------------- access control */

    /**
     * @dataProvider adminPageProvider
     */
    public function testAnonymousIsRedirectedToLogin($uri): void
    {
        $client = $this->client;
        $client->request('GET', $uri);

        $this->assertSame(302, $client->getResponse()->getStatusCode());
        $this->assertSame('http://localhost/login', $client->getResponse()->headers->get('Location'));
    }

    /**
     * @dataProvider adminPageProvider
     */
    public function testUsersAreDenied($uri): void
    {
        $client = $this->createAuthenticatedClient('test');
        $client->request('GET', $uri);

        $this->assertSame(403, $client->getResponse()->getStatusCode());
    }

    /**
     * Write routes are denied to users before anything is done.
     *
     * @dataProvider writeRouteProvider
     */
    public function testUsersCannotWrite($method, $uri, array $parameters): void
    {
        $client = $this->createAuthenticatedClient('test');
        $before = $this->db()->fetchAll('SELECT id, name FROM cycle ORDER BY id');

        $client->request($method, $uri, $parameters);

        $this->assertSame(403, $client->getResponse()->getStatusCode());
        $this->assertSame($before, $this->db()->fetchAll('SELECT id, name FROM cycle ORDER BY id'));
    }

    public function writeRouteProvider(): array
    {
        return [
            'create' => ['POST', '/admin/cycle/create', ['appbundle_cycle' => ['code' => 'X', 'name' => 'Hacked', 'position' => 99]]],
            'update' => ['POST', '/admin/cycle/1/update', ['appbundle_cycle' => ['name' => 'Hacked']]],
            'delete' => ['POST', '/admin/cycle/1/delete', []],
            'run a command' => ['POST', '/admin/command/', ['command' => 'scenario']],
            'toggle a comment' => ['GET', '/admin/comment/toggle_hidden/1', []],
            'delete a decklist' => ['GET', '/admin/decklist/delete/1', []],
        ];
    }

    /* ------------------------------------------------------ admin pages */

    /**
     * The pages with a text snapshot (the big lists are checked by testBigLists, the statistics
     * by testStatistics).
     *
     * @return (array|null)
     */
    public function snapshotPageProvider()
    {
        return array_filter($this->adminPageProvider(), fn (array $page): bool => null !== $page[1]);
    }

    /**
     * @dataProvider snapshotPageProvider
     */
    public function testAdminPage($uri, $snapshot): void
    {
        $client = $this->createAuthenticatedClient('admin');
        $crawler = $client->request('GET', $uri);

        $this->assertSame(200, $client->getResponse()->getStatusCode(), "GET $uri");
        $this->assertMatchesSnapshot("admin/$snapshot.txt", self::pageText($crawler));
    }

    /**
     * @dataProvider bigListProvider
     */
    public function testBigLists($uri, $table): void
    {
        $client = $this->createAuthenticatedClient('admin');
        $crawler = $client->request('GET', $uri);

        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $this->assertCount((int) $this->db()->fetchColumn("SELECT COUNT(*) FROM $table"), $crawler->filter('table tbody tr'));
    }

    public function bigListProvider(): array
    {
        return [
            'cards' => ['/admin/card/', 'card'],
            'card printings' => ['/admin/card-printing/', 'card_printing'],
        ];
    }

    /**
     * The statistics are JSON, for a month (default: last month, so the tests give one).
     *
     * @dataProvider statisticsProvider
     */
    public function testStatistics($uri, $snapshot): void
    {
        $client = $this->createAuthenticatedClient('admin');
        $client->request('GET', $uri);

        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $this->assertSame('application/json', $client->getResponse()->headers->get('Content-Type'));
        $this->assertMatchesJsonSnapshot("admin/$snapshot", $client->getResponse()->getContent());
    }

    public function statisticsProvider(): array
    {
        return [
            'decks and users by cycle' => ['/admin/stat?month=2015-08', 'stat_2015-08'],
            'packs' => ['/admin/stat_packs?month=2015-08', 'stat_packs_2015-08'],
        ];
    }

    public function testUnknownEntity(): void
    {
        $client = $this->createAuthenticatedClient('admin');
        $crawler = $client->request('GET', '/admin/cycle/999/show');

        $this->assertSame(404, $client->getResponse()->getStatusCode());
        $this->assertSame('Unable to find Cycle entity. (404 Not Found)', trim($crawler->filter('title')->text()));
    }

    /**
     * The per-card statistics are precomputed by a cron (app:stats:precompute-cards) into
     * stat_cards_cache, which is empty in the test database.
     */
    public function testCardStatisticsNotPrecomputed(): void
    {
        $client = $this->createAuthenticatedClient('admin');
        $client->request('GET', '/admin/stat_cards?month=2015-08');

        $this->assertSame(503, $client->getResponse()->getStatusCode());
        $this->assertSame('Per-card stats for 2015-08 have not been precomputed yet. Run `php bin/console app:stats:precompute-cards 2015-08` (scheduled via cron).', $client->getResponse()->getContent());
    }

    /**
     * The user's last update date is changed by each login (last_login, then Gedmo
     * timestampable): it is masked in the snapshot.
     */
    public function testUserPage(): void
    {
        $client = $this->createAuthenticatedClient('admin');
        $crawler = $client->request('GET', '/admin/user/show/1');

        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $text = preg_replace('/^(Date of last update\n).*$/m', '$1<masked>', self::pageText($crawler));
        $this->assertMatchesSnapshot('admin/user_show_1.txt', $text);
    }

    /**
     * "Block" / "Unblock" on the user page (a GET route that writes).
     */
    public function testBlockAndUnblockAUser(): void
    {
        $client = $this->createAuthenticatedClient('admin');
        try {
            $client->request('GET', '/admin/user/toggle_locked/1');
            $this->assertSame(302, $client->getResponse()->getStatusCode());
            $this->assertSame('/admin/user/show/1', $client->getResponse()->headers->get('Location'));
            $this->assertSame('1', $this->db()->fetchColumn('SELECT locked FROM user WHERE id = 1'));

            $crawler = $client->followRedirect();
            $this->assertSame('Unblock', trim($crawler->filter('a[href="/admin/user/toggle_locked/1"]')->text()));

            $client->request('GET', '/admin/user/toggle_locked/1');
            $this->assertSame('0', $this->db()->fetchColumn('SELECT locked FROM user WHERE id = 1'));
        } finally {
            $this->db()->update('user', ['locked' => 0], ['id' => 1]);
        }
    }

    /**
     * A blocked user cannot log in: User::isAccountNonLocked() reads the "locked" column
     * (FOSUserBundle 2's own version always returns true).
     */
    public function testBlockedUserCannotLogIn(): void {
        $client = static::createClient();
        $this->db($client)->update('user', ['locked' => 1], ['username' => 'test']);
        try {
            $crawler = $client->request('GET', '/login');
            $client->submit($crawler->selectButton('_submit')->form(['_username' => 'test', '_password' => 'test']));
            $this->assertTrue($client->getResponse()->isRedirect());
            $this->assertSame('/login', parse_url((string) $client->getResponse()->headers->get('Location'), PHP_URL_PATH));

            $client->request('GET', '/decks');
            $this->assertSame(302, $client->getResponse()->getStatusCode());
            $this->assertSame('/login', parse_url((string) $client->getResponse()->headers->get('Location'), PHP_URL_PATH));
        } finally {
            $this->db()->update('user', ['locked' => 0], ['username' => 'test']);
        }
    }
}
