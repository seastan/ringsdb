<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * Strict tests of the private API (/api/private/*), used by the site's own JavaScript
 * (builder multi-deck mode, deck picker of fellowships and quest logs, custom packs).
 *
 * It is authenticated by the regular session: the tests log in with the login form, and send
 * the requests with AJAX like the JavaScript does. Response bodies are compared to snapshots
 * stored in Tests/Resources/snapshots/api/private/ (see JsonSnapshotTrait).
 *
 * Fixtures: "test" owns decks 1-4, decklists 1-4 and one custom pack; "admin" owns nothing.
 * Neither shares their decks.
 */
class ApiPrivateControllerTest extends WebTestCase
{
    use JsonSnapshotTrait;

    public const LAST_MODIFIED = 'Sun, 16 Aug 2015 00:00:00 GMT';

    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
    }

    protected function tearDown(): void
    {
        $this->client->getContainer()->get('doctrine')->getConnection()
            ->update('user', ['is_share_decks' => 0], ['username' => 'test']);
        parent::tearDown();
    }

    /* ------------------------------------------------------------ helpers */

    private function createAuthenticatedClient(string $username): KernelBrowser
    {
        $client = $this->client;
        $crawler = $client->request('GET', '/login');
        $client->submit($crawler->selectButton('_submit')->form(['_username' => $username, '_password' => $username]));
        $this->assertTrue($client->getResponse()->isRedirect(), "Login as $username failed");

        return $client;
    }

    /**
     * @param array<string, string> $headers
     */
    private function ajax(KernelBrowser $client, string $uri, array $headers = []): Response
    {
        $client->request('GET', $uri, [], [], $headers + ['HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest']);

        return $client->getResponse();
    }

    /**
     * Responses with data carry a Last-Modified header, to be revalidated by the browser (304).
     *
     * Since Symfony 3.4, the session listener makes every response of a request that used the
     * session "max-age=0, must-revalidate, private" (it was "private, must-revalidate" with data,
     * "no-cache" without).
     */
    private function assertCacheableJson(Response $response): void
    {
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('application/json', $response->headers->get('Content-Type'));
        $this->assertSame('max-age=0, must-revalidate, private', $response->headers->get('Cache-Control'));
        $this->assertSame(self::LAST_MODIFIED, $response->headers->get('Last-Modified'));
    }

    private function assertUncachedJson(Response $response): void
    {
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('application/json', $response->headers->get('Content-Type'));
        $this->assertSame('max-age=0, must-revalidate, private', $response->headers->get('Cache-Control'));
        $this->assertNull($response->headers->get('Last-Modified'));
    }

    /* ------------------------------------------------------- JSON bodies */
    /**
     * [user, uri, snapshot name].
     * @return array<string, string[]>
     */
    public function cacheableEndpointProvider(): array
    {
        return [
            'my decks: decklists then decks' => ['test', '/api/private/decks', 'private/decks_test'],
            'my decks by username' => ['test', '/api/private/decks_by_user/test', 'private/decks_test'],
            // private decks are only listed for their owner, even when they are shared
            "another user's decklists only" => ['admin', '/api/private/decks_by_user/test', 'private/decklists_test'],
            'own deck' => ['test', '/api/private/deck/load/1', 'private/deck_1'],
        ];
    }

    /**
     * @dataProvider cacheableEndpointProvider
     */
    public function testCacheableEndpoint(string $user, string $uri, string $snapshot): void
    {
        $client = $this->createAuthenticatedClient($user);
        $response = $this->ajax($client, $uri);

        $this->assertCacheableJson($response);
        $this->assertMatchesJsonSnapshot($snapshot, $response->getContent());
    }

    /**
     * [user, uri, expected JSON].
     * @return array<string, string[]|never[][]|array<string, bool|string>[]>
     */
    public function uncachedEndpointProvider(): array
    {
        $notShared = 'You are not allowed to view this deck. To get access, you can ask the deck owner to enable "Share my decks" on their account.';

        return [
            'no deck' => ['admin', '/api/private/decks', []],
            'user without deck' => ['test', '/api/private/decks_by_user/admin', []],
            'unknown user' => ['test', '/api/private/decks_by_user/nobody', ['success' => false, 'error' => 'This user does not exist.']],
            'unknown deck' => ['test', '/api/private/deck/load/999', ['success' => false, 'error' => 'This deck does not exists.']],
            'deck not shared' => ['admin', '/api/private/deck/load/1', ['success' => false, 'error' => $notShared]],
            'no custom pack' => ['admin', '/api/private/custom-packs', []],
        ];
    }

    /**
     * Errors are answered with a 200 and {"success": false, "error": ...}.
     *
     * @dataProvider uncachedEndpointProvider
     */
    public function testUncachedEndpoint(string $user, string $uri, array $expected): void
    {
        $client = $this->createAuthenticatedClient($user);
        $response = $this->ajax($client, $uri);

        $this->assertUncachedJson($response);
        $this->assertSame($expected, json_decode($response->getContent(), true));
    }

    public function testCustomPacks(): void
    {
        $client = $this->createAuthenticatedClient('test');
        $response = $this->ajax($client, '/api/private/custom-packs');

        $this->assertUncachedJson($response);
        $this->assertMatchesJsonSnapshot('private/custom_packs_test', $response->getContent());
    }

    public function testSharedDeckCanBeLoadedByAnotherUser(): void
    {
        $client = $this->createAuthenticatedClient('admin');
        $client->getContainer()->get('doctrine')->getConnection()->update('user', ['is_share_decks' => 1], ['username' => 'test']);

        $response = $this->ajax($client, '/api/private/deck/load/1');
        $this->assertCacheableJson($response);
        $this->assertMatchesJsonSnapshot('private/deck_1', $response->getContent());

        // sharing does not add the private decks to the list
        $response = $this->ajax($client, '/api/private/decks_by_user/test');
        $this->assertMatchesJsonSnapshot('private/decklists_test', $response->getContent());
    }

    /* ------------------------------------------------------- HTTP caching */

    /**
     * @dataProvider cacheableEndpointProvider
     */
    public function testNotModifiedSince(string $user, string $uri): void
    {
        $client = $this->createAuthenticatedClient($user);

        $response = $this->ajax($client, $uri, ['HTTP_IF_MODIFIED_SINCE' => self::LAST_MODIFIED]);
        $this->assertSame(304, $response->getStatusCode());
        $this->assertSame('', $response->getContent());

        $response = $this->ajax($client, $uri, ['HTTP_IF_MODIFIED_SINCE' => 'Sat, 01 Jan 2000 00:00:00 GMT']);
        $this->assertSame(200, $response->getStatusCode());
    }

    /* ----------------------------------------------------------- security */
    /**
     * @return array<string, string[]>
     */
    public function privateUriProvider(): array
    {
        return [
            'my decks' => ['/api/private/decks'],
            'decks by user' => ['/api/private/decks_by_user/test'],
            'deck' => ['/api/private/deck/load/1'],
            'custom packs' => ['/api/private/custom-packs'],
        ];
    }

    /**
     * @dataProvider privateUriProvider
     */
    public function testAnonymousAjaxIsDenied(string $uri): void
    {
        $client = $this->client;
        $response = $this->ajax($client, $uri);

        $this->assertSame(403, $response->getStatusCode());
        $this->assertSame('application/json', $response->headers->get('Content-Type'));
        $this->assertSame(['success' => false, 'message' => 'Access Denied.'], json_decode($response->getContent(), true));
    }

    /**
     * @dataProvider privateUriProvider
     */
    public function testAnonymousIsRedirectedToLogin(string $uri): void
    {
        $client = $this->client;
        $client->request('GET', $uri);

        $this->assertSame(302, $client->getResponse()->getStatusCode());
        $this->assertSame('http://localhost/login', $client->getResponse()->headers->get('Location'));
    }

    public function testPostIsNotAllowed(): void
    {
        $client = $this->createAuthenticatedClient('test');
        $client->request('POST', '/api/private/decks');

        $this->assertSame(405, $client->getResponse()->getStatusCode());
    }
}
