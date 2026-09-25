<?php

namespace AppBundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Client;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * Strict tests of the public API (/api/public/*).
 *
 * Response bodies are compared to snapshots stored in Tests/Resources/snapshots/api/.
 * Both sides are decoded and re-encoded the same way before comparison, so the check is
 * strict on structure, key order, value types and {} vs [], but ignores whitespace and
 * escaping (e.g. "\/" vs "/", "ú" vs "ú").
 *
 * To (re)generate the snapshots, run the tests with UPDATE_SNAPSHOTS=1, then review the diff:
 *   docker compose exec -e UPDATE_SNAPSHOTS=1 -u www-data symfony php bin/simple-phpunit
 *
 * Relies on ringsdb_bootstrap.sql (cards, packs, scenarios) and on the fixtures (decklists).
 *
 * @coversNothing
 */
class ApiControllerTest extends WebTestCase {
    const CACHE_CONTROL = 'max-age=600, public';

    /* ------------------------------------------------------------ helpers */

    private static function normalizeJson($json) {
        $data = json_decode($json);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \InvalidArgumentException('Invalid JSON: ' . json_last_error_msg());
        }

        return json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION) . "\n";
    }

    private function assertMatchesJsonSnapshot($name, $json) {
        $file = __DIR__ . '/../Resources/snapshots/api/' . $name . '.json';
        $actual = self::normalizeJson($json);

        if (getenv('UPDATE_SNAPSHOTS')) {
            if (!is_dir(dirname($file))) {
                mkdir(dirname($file), 0755, true);
            }
            file_put_contents($file, $actual);
        }

        $this->assertFileExists($file, "Missing snapshot $name, run the tests with UPDATE_SNAPSHOTS=1 to create it");
        $this->assertSame(file_get_contents($file), $actual, "Response differs from snapshot $name");
    }

    private function get(Client $client, $uri, array $headers = []) {
        $client->request('GET', $uri, [], [], $headers);

        return $client->getResponse();
    }

    private function assertApiHeaders(Response $response, $contentType, $lastModified) {
        $this->assertSame($contentType, $response->headers->get('Content-Type'));
        $this->assertSame(self::CACHE_CONTROL, $response->headers->get('Cache-Control'));
        $this->assertSame('*', $response->headers->get('Access-Control-Allow-Origin'));
        $this->assertSame($lastModified, $response->headers->get('Last-Modified'));
    }

    /* ------------------------------------------------------- JSON bodies */

    /**
     * [snapshot name, uri, expected Last-Modified header (null if none)]
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
     */
    public function testJsonEndpoint($snapshot, $uri, $lastModified) {
        $client = static::createClient();
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

    public function testSearchLastModified() {
        $client = static::createClient();
        $response = $this->get($client, '/api/public/cards/search/Aragorn');

        $this->assertApiHeaders($response, 'application/json', 'Fri, 09 Sep 2022 12:59:33 GMT');
    }

    public function testSearchIgnoresJsonp() {
        $client = static::createClient();
        $response = $this->get($client, '/api/public/cards/search/Aragorn?jsonp=myCallback');

        $this->assertSame('application/json', $response->headers->get('Content-Type'));
        $this->assertMatchesJsonSnapshot('search_aragorn', $response->getContent());
    }

    /* -------------------------------------------------------------- JSONP */

    /**
     * @dataProvider jsonpEndpointProvider
     */
    public function testJsonp($snapshot, $uri) {
        $client = static::createClient();
        $response = $this->get($client, $uri . '?jsonp=myCallback');

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('application/javascript', $response->headers->get('Content-Type'));
        $this->assertRegExp('/^myCallback\((.*)\)$/s', $response->getContent());
        $json = preg_replace('/^myCallback\((.*)\)$/s', '$1', $response->getContent());
        $this->assertMatchesJsonSnapshot($snapshot, $json);
    }

    public function jsonpEndpointProvider() {
        return [
            'packs' => ['packs', '/api/public/packs/'],
            'card' => ['card_01001', '/api/public/card/01001'],
            'cards by pack' => ['cards_Core', '/api/public/cards/Core'],
            'decklist' => ['decklist_1', '/api/public/decklist/1'],
            'decklists by date' => ['decklists_2015-08-16', '/api/public/decklists/by_date/2015-08-16'],
            'top decklists by card' => ['top_decklists_01001', '/api/public/decklists/top_by_card/01001'],
            'scenario' => ['scenario_1', '/api/public/scenario/1'],
        ];
    }

    /* ------------------------------------------------------- HTTP caching */

    /**
     * @dataProvider cachedEndpointProvider
     */
    public function testNotModifiedSince($uri, $lastModified) {
        $client = static::createClient();

        $response = $this->get($client, $uri, ['HTTP_IF_MODIFIED_SINCE' => $lastModified]);
        $this->assertSame(304, $response->getStatusCode());
        $this->assertSame('', $response->getContent());

        $response = $this->get($client, $uri, ['HTTP_IF_MODIFIED_SINCE' => 'Sat, 01 Jan 2000 00:00:00 GMT']);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertNotEmpty($response->getContent());
    }

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
     */
    public function testErrors($uri, $expectedStatus) {
        $client = static::createClient();
        $response = $this->get($client, $uri);

        $this->assertSame($expectedStatus, $response->getStatusCode());
    }

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
     */
    public function testUnsupportedFormatOnCardsByPack($format, $contentType) {
        $client = static::createClient();
        $response = $this->get($client, '/api/public/cards/Core.' . $format);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame($contentType, $response->headers->get('Content-Type'));
        $this->assertSame("$format format not supported. Only json is supported.", $response->getContent());
    }

    public function unsupportedFormatProvider() {
        return [
            'xml' => ['xml', 'text/xml; charset=UTF-8'],
            'xls' => ['xls', 'text/html; charset=UTF-8'],
            'xlsx' => ['xlsx', 'text/html; charset=UTF-8'],
        ];
    }

    public function testPostIsNotAllowed() {
        $client = static::createClient();
        $client->request('POST', '/api/public/card/01001');

        $this->assertSame(405, $client->getResponse()->getStatusCode());
    }

    /* ------------------------------------------ endpoints outside ApiController */

    public function testPublishedCustomPacks() {
        $client = static::createClient();
        $response = $this->get($client, '/api/public/custom-packs/published');

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('application/json', $response->headers->get('Content-Type'));
        $this->assertMatchesJsonSnapshot('custom_packs_published', $response->getContent());
    }

    public function testUserInfoAnonymous() {
        $client = static::createClient();
        $response = $this->get($client, '/api/public/user/info');

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('application/json', $response->headers->get('Content-Type'));
        $this->assertSame('null', $response->getContent());
    }

    public function testUserInfoAuthenticated() {
        $client = static::createClient();
        $crawler = $client->request('GET', '/login');
        $client->submit($crawler->selectButton('_submit')->form(['_username' => 'test', '_password' => 'test']));
        $this->assertTrue($client->getResponse()->isRedirect());

        $response = $this->get($client, '/api/public/user/info');
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('application/json', $response->headers->get('Content-Type'));
        $this->assertMatchesJsonSnapshot('user_info_test', $response->getContent());

        $response = $this->get($client, '/api/public/user/info?decklist_id=1');
        $this->assertMatchesJsonSnapshot('user_info_test_decklist_1', $response->getContent());
    }
}
