<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Client;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * Strict tests of the public API (/api/public/*).
 *
 * Response bodies are compared to snapshots stored in Tests/Resources/snapshots/api/
 * (see JsonSnapshotTrait).
 *
 * Relies on ringsdb_bootstrap.sql (cards, packs, scenarios) and on the fixtures (decklists).
 */
class ApiControllerTest extends WebTestCase {
    use JsonSnapshotTrait;

    private KernelBrowser $client;

    const CACHE_CONTROL = 'max-age=600, public';

    protected function setUp(): void
    {
        $this->client = static::createClient();
    }

    /* ------------------------------------------------------------ helpers */

    /**
     * @param mixed $uri
     * @return \Symfony\Component\HttpFoundation\Response
     */
    private function get(Client $client, $uri, array $headers = []) {
        $client->request('GET', $uri, [], [], $headers);

        return $client->getResponse();
    }

    /**
     * @param mixed $contentType
     * @param mixed $lastModified
     */
    private function assertApiHeaders(Response $response, $contentType, $lastModified): void {
        $this->assertSame($contentType, $response->headers->get('Content-Type'));
        $this->assertSame(self::CACHE_CONTROL, $response->headers->get('Cache-Control'));
        $this->assertSame('*', $response->headers->get('Access-Control-Allow-Origin'));
        $this->assertSame($lastModified, $response->headers->get('Last-Modified'));
    }

    /* ------------------------------------------------------- JSON bodies */

    /**
     * [snapshot name, uri, expected Last-Modified header (null if none)]
     * @return array
     */
    public function jsonEndpointProvider() {
        return [
            'packs' => ['packs', '/api/public/packs/', 'Wed, 25 Mar 2026 16:18:09 GMT'],
            'card' => ['card_01001', '/api/public/card/01001', 'Sat, 17 Nov 2018 19:42:24 GMT'],
            'card with format' => ['card_01001', '/api/public/card/01001.json', 'Sat, 17 Nov 2018 19:42:24 GMT'],
            // Last-Modified is the most recent of the cards and of their printings
            'all cards' => ['cards', '/api/public/cards/', 'Thu, 23 Jul 2026 06:52:11 GMT'],
            'cards by pack' => ['cards_Core', '/api/public/cards/Core', 'Wed, 04 Mar 2020 14:07:55 GMT'],
            'cards by pack, case insensitive' => ['cards_Core', '/api/public/cards/core.json', 'Wed, 04 Mar 2020 14:07:55 GMT'],
            'search by name' => ['search_aragorn', '/api/public/cards/search/Aragorn', null],
            'search by pack and type' => ['search_pack_type', '/api/public/cards/search/e:Core%20t:hero', null],
            'search without result' => ['empty_array', '/api/public/cards/search/zzzzqqq', null],
            'decklist' => ['decklist_1', '/api/public/decklist/1', 'Sun, 16 Aug 2015 00:00:00 GMT'],
            'decklists by date' => ['decklists_2015-08-16', '/api/public/decklists/by_date/2015-08-16', null],
            'decklists by date, none' => ['empty_array', '/api/public/decklists/by_date/2000-01-01', null],
            'top decklists by card' => ['top_decklists_01001', '/api/public/decklists/top_by_card/01001', 'Sun, 16 Aug 2015 00:00:00 GMT'],
            'top decklists by unknown card' => ['empty_array', '/api/public/decklists/top_by_card/99999', null],
            'scenario' => ['scenario_1', '/api/public/scenario/1', 'Wed, 13 Mar 2019 18:44:33 GMT'],
        ];
    }

    /**
     * @dataProvider jsonEndpointProvider
     * @param mixed $snapshot
     * @param mixed $uri
     * @param mixed $lastModified
     */
    public function testJsonEndpoint($snapshot, $uri, $lastModified): void {
        $client = $this->client;
        $response = $this->get($client, $uri);

        $this->assertSame(200, $response->getStatusCode());
        // search: Last-Modified depends on the matching cards, it is only checked when there are some
        if ($lastModified !== null || $response->getContent() === '[]') {
            $this->assertApiHeaders($response, 'application/json', $lastModified);
        } else {
            $this->assertSame('application/json', $response->headers->get('Content-Type'));
            $this->assertSame(self::CACHE_CONTROL, $response->headers->get('Cache-Control'));
            $this->assertSame('*', $response->headers->get('Access-Control-Allow-Origin'));
        }
        $this->assertMatchesJsonSnapshot($snapshot, $response->getContent());
    }

    public function testSearchLastModified(): void {
        $client = $this->client;
        $response = $this->get($client, '/api/public/cards/search/Aragorn');

        $this->assertApiHeaders($response, 'application/json', 'Fri, 09 Sep 2022 12:59:33 GMT');
    }

    /* -------------------------------------------------------------- JSONP */

    /**
     * @dataProvider jsonpEndpointProvider
     * @param mixed $snapshot
     * @param mixed $uri
     */
    public function testJsonp($snapshot, $uri): void {
        $client = $this->client;
        $response = $this->get($client, $uri . '?jsonp=myCallback');

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('application/javascript', $response->headers->get('Content-Type'));
        // the comment prefix protects against content sniffing (JsonResponse::setCallback())
        $this->assertRegExp('/^\/\*\*\/myCallback\((.*)\);$/s', $response->getContent());
        $json = preg_replace('/^\/\*\*\/myCallback\((.*)\);$/s', '$1', $response->getContent());
        $this->assertMatchesJsonSnapshot($snapshot, $json);
    }

    /**
     * Fixed: the callback was echoed as is into the script (XSS). It must be a JavaScript
     * identifier, dots and brackets allowed, not a reserved word; otherwise 400.
     *
     * @dataProvider invalidJsonpProvider
     */
    public function testInvalidJsonpCallbackIsRefused(string $callback): void {
        $client = $this->client;
        $response = $this->get($client, '/api/public/card/01001?jsonp=' . urlencode($callback));

        $this->assertSame(400, $response->getStatusCode());
        // not a script: the callback is not echoed as code
        $this->assertNotSame('application/javascript', $response->headers->get('Content-Type'));
        $this->assertNotContains('/**/', (string) $response->getContent());
    }

    /**
     * @return array<string, array{string}>
     */
    public function invalidJsonpProvider() {
        return [
            'script injection' => ['alert(document.cookie)//'],
            'markup' => ['<script>alert(1)</script>'],
            'reserved word' => ['function'],
        ];
    }

    public function testJsonpCallbackWithDots(): void {
        $client = $this->client;
        $response = $this->get($client, '/api/public/card/01001?jsonp=jQuery123.cb_4');

        $this->assertSame(200, $response->getStatusCode());
        $this->assertStringStartsWith('/**/jQuery123.cb_4(', (string) $response->getContent());
    }

    /**
     * An empty callback is ignored: plain JSON.
     */
    public function testEmptyJsonpCallbackIsIgnored(): void {
        $client = $this->client;
        $response = $this->get($client, '/api/public/card/01001?jsonp=');

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('application/json', $response->headers->get('Content-Type'));
        $this->assertStringStartsWith('{', (string) $response->getContent());
    }

    /**
     * @return array
     */
    public function jsonpEndpointProvider() {
        return [
            'packs' => ['packs', '/api/public/packs/'],
            'card' => ['card_01001', '/api/public/card/01001'],
            'cards by pack' => ['cards_Core', '/api/public/cards/Core'],
            'decklist' => ['decklist_1', '/api/public/decklist/1'],
            'decklists by date' => ['decklists_2015-08-16', '/api/public/decklists/by_date/2015-08-16'],
            'top decklists by card' => ['top_decklists_01001', '/api/public/decklists/top_by_card/01001'],
            'scenario' => ['scenario_1', '/api/public/scenario/1'],
            'card search' => ['search_aragorn', '/api/public/cards/search/Aragorn'],
        ];
    }

    /* ------------------------------------------------------- HTTP caching */

    /**
     * @dataProvider cachedEndpointProvider
     * @param mixed $uri
     * @param mixed $lastModified
     */
    public function testNotModifiedSince($uri, $lastModified): void {
        $client = $this->client;

        $response = $this->get($client, $uri, ['HTTP_IF_MODIFIED_SINCE' => $lastModified]);
        $this->assertSame(304, $response->getStatusCode());
        $this->assertSame('', $response->getContent());

        $response = $this->get($client, $uri, ['HTTP_IF_MODIFIED_SINCE' => 'Sat, 01 Jan 2000 00:00:00 GMT']);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertNotEmpty($response->getContent());
    }

    /**
     * @return array
     */
    public function cachedEndpointProvider() {
        return [
            'packs' => ['/api/public/packs/', 'Wed, 25 Mar 2026 16:18:09 GMT'],
            'card' => ['/api/public/card/01001', 'Sat, 17 Nov 2018 19:42:24 GMT'],
            'all cards' => ['/api/public/cards/', 'Thu, 23 Jul 2026 06:52:11 GMT'],
            'cards by pack' => ['/api/public/cards/Core', 'Wed, 04 Mar 2020 14:07:55 GMT'],
            'search' => ['/api/public/cards/search/Aragorn', 'Fri, 09 Sep 2022 12:59:33 GMT'],
            'decklist' => ['/api/public/decklist/1', 'Sun, 16 Aug 2015 00:00:00 GMT'],
            'top decklists by card' => ['/api/public/decklists/top_by_card/01001', 'Sun, 16 Aug 2015 00:00:00 GMT'],
            'scenario' => ['/api/public/scenario/1', 'Wed, 13 Mar 2019 18:44:33 GMT'],
        ];
    }

    /* ------------------------------------------------------------- errors */

    /**
     * @dataProvider errorProvider
     * @param mixed $uri
     * @param mixed $expectedStatus
     */
    public function testErrors($uri, $expectedStatus): void {
        $client = $this->client;
        $response = $this->get($client, $uri);

        $this->assertSame($expectedStatus, $response->getStatusCode());
    }

    /**
     * @return array
     */
    public function errorProvider() {
        return [
            'unknown pack' => ['/api/public/cards/nope', 404],
            'unknown decklist' => ['/api/public/decklist/999', 404],
            'non numeric decklist id' => ['/api/public/decklist/abc', 404],
            'invalid date' => ['/api/public/decklists/by_date/2015-8-16', 404],
            'unsupported card format' => ['/api/public/card/01001.xml', 404],
            'unknown card' => ['/api/public/card/99999', 404],
            'unknown scenario' => ['/api/public/scenario/9999', 404],
        ];
    }

    /**
     * @dataProvider unsupportedFormatProvider
     * @param mixed $format
     * @param mixed $contentType
     */
    public function testUnsupportedFormatOnCardsByPack($format, $contentType): void {
        $client = $this->client;
        $response = $this->get($client, '/api/public/cards/Core.' . $format);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame($contentType, $response->headers->get('Content-Type'));
        $this->assertSame("$format format not supported. Only json is supported.", $response->getContent());
    }

    /**
     * @return array
     */
    public function unsupportedFormatProvider() {
        return [
            'xml' => ['xml', 'text/xml; charset=UTF-8'],
            'xls' => ['xls', 'text/html; charset=UTF-8'],
            'xlsx' => ['xlsx', 'text/html; charset=UTF-8'],
        ];
    }

    public function testPostIsNotAllowed(): void {
        $client = $this->client;
        $client->request('POST', '/api/public/card/01001');

        $this->assertSame(405, $client->getResponse()->getStatusCode());
    }

    /* ------------------------------------------ endpoints outside ApiController */

    public function testPublishedCustomPacks(): void {
        $client = $this->client;
        $response = $this->get($client, '/api/public/custom-packs/published');

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('application/json', $response->headers->get('Content-Type'));
        $this->assertMatchesJsonSnapshot('custom_packs_published', $response->getContent());
    }

    public function testUserInfoAnonymous(): void {
        $client = $this->client;
        $response = $this->get($client, '/api/private/user/info');

        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame('http://localhost/login', $client->getResponse()->headers->get('Location'));
    }

    public function testUserInfoAuthenticated(): void {
        $client = $this->client;
        $crawler = $client->request('GET', '/login');
        $client->submit($crawler->selectButton('_submit')->form(['_username' => 'test', '_password' => 'test']));
        $this->assertTrue($client->getResponse()->isRedirect());

        $response = $this->get($client, '/api/private/user/info');
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('application/json', $response->headers->get('Content-Type'));
        $this->assertMatchesJsonSnapshot('user_info_test', $response->getContent());

        $response = $this->get($client, '/api/private/user/info?decklist_id=1');
        $this->assertMatchesJsonSnapshot('user_info_test_decklist_1', $response->getContent());
    }
}
