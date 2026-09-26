<?php

namespace AppBundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Client;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Collection forms: owned packs (POST /collection/packs/save), art preferences
 * (POST /collection/art/save, from the card modal), custom packs (/collection/custom-pack/*).
 *
 * Fixtures: "test" owns custom pack 1 ("Test Custom Pack", published, enabled: 1x 01001, 3x
 * 01016); "admin" owns none. Everything is restored in tearDown().
 */
class CollectionTest extends WebTestCase {
    /** @var array */
    private $fixtureUsers;
    /** @var array */
    private $fixturePack;
    /** @var int[] */
    private $maxIds = [];

    protected function setUp() {
        $connection = $this->db(static::createClient());
        $this->fixtureUsers = $connection->fetchAll('SELECT id, owned_packs, art_preferences FROM user ORDER BY id');
        $this->fixturePack = [
            $connection->fetchAssoc('SELECT * FROM user_custom_pack WHERE id = 1'),
            $connection->fetchAll('SELECT * FROM user_custom_pack_card WHERE custom_pack_id = 1 ORDER BY id'),
        ];
        foreach (['user_custom_pack', 'user_custom_pack_card'] as $table) {
            $this->maxIds[$table] = (int) $connection->fetchColumn("SELECT MAX(id) FROM $table");
        }
    }

    protected function tearDown() {
        $connection = $this->db(static::createClient());
        $connection->exec("DELETE FROM user_custom_pack_card WHERE custom_pack_id > {$this->maxIds['user_custom_pack']} OR id > {$this->maxIds['user_custom_pack_card']}");
        $connection->exec("DELETE FROM user_custom_pack WHERE id > {$this->maxIds['user_custom_pack']}");
        list($pack, $cards) = $this->fixturePack;
        if (!$connection->fetchColumn('SELECT COUNT(*) FROM user_custom_pack WHERE id = 1')) {
            $connection->insert('user_custom_pack', $pack);
        } else {
            $connection->update('user_custom_pack', $pack, ['id' => 1]);
        }
        $connection->exec('DELETE FROM user_custom_pack_card WHERE custom_pack_id = 1');
        foreach ($cards as $card) {
            $connection->insert('user_custom_pack_card', $card);
        }
        foreach ($this->fixtureUsers as $user) {
            $connection->update('user', $user, ['id' => $user['id']]);
        }
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

    private function packCards(Client $client, $packId) {
        $rows = $this->db($client)->fetchAll('SELECT c.code, e.quantity FROM user_custom_pack_card e JOIN card c ON c.id = e.card_id WHERE e.custom_pack_id = ? ORDER BY e.id', [$packId]);

        return array_map('intval', array_column($rows, 'quantity', 'code'));
    }

    private function fetchPack(Client $client, $id) {
        return $this->db($client)->fetchAssoc('SELECT p.name, p.code, p.is_enabled, p.is_published, u.username FROM user_custom_pack p JOIN user u ON u.id = p.user_id WHERE p.id = ?', [$id]);
    }

    /**
     * Submits the custom pack form; the page's JavaScript serializes the card list into the
     * hidden "cards_json" field.
     */
    private function submitPackForm(Client $client, $pageUri, $name, array $cards) {
        $crawler = $client->request('GET', $pageUri);
        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $form = $crawler->filter('#custom-pack-form')->form(['name' => $name, 'cards_json' => json_encode($cards)]);
        $client->submit($form);

        return $client->getResponse();
    }

    /* -------------------------------------------------------- owned packs */

    public function testSaveOwnedPacks() {
        $client = $this->createAuthenticatedClient();
        $crawler = $client->request('GET', '/collection/packs');
        $form = $crawler->filter('form[action="/collection/packs/save"]')->form(['selected-packs' => '1:2,2,3']);
        $crawler = $client->submit($form);

        // the collection page is rendered directly (forward), with a flash message
        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $this->assertContains('Collection saved.', $crawler->filter('body')->text());
        $this->assertSame('1:2,2,3', $this->db($client)->fetchColumn('SELECT owned_packs FROM user WHERE id = 1'));

        $client->request('GET', '/api/public/user/info');
        $this->assertSame('1:2,2,3', json_decode($client->getResponse()->getContent(), true)['owned_packs']);
    }

    public function testInvalidPackSelectionIsRefused() {
        $client = $this->createAuthenticatedClient();
        $client->request('POST', '/collection/packs/save', ['selected-packs' => '1,2; DROP TABLE user']);

        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $this->assertSame('Invalid pack selection.', $client->getResponse()->getContent());
        $this->assertNull($this->db($client)->fetchColumn('SELECT owned_packs FROM user WHERE id = 1'));
    }

    /* ---------------------------------------------------- art preferences */

    public function testArtPreferences() {
        $client = $this->createAuthenticatedClient();
        $save = function ($cardCode, $packCode) use ($client) {
            $client->request('POST', '/collection/art/save', ['card_code' => $cardCode, 'pack_code' => $packCode], [], ['HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest']);
            $this->assertSame(200, $client->getResponse()->getStatusCode());
            $this->assertSame('{"success":true}', $client->getResponse()->getContent());

            return $this->db($client)->fetchColumn('SELECT art_preferences FROM user WHERE id = 1');
        };

        $this->assertSame('{"01001":"RevCore"}', $save('01001', 'RevCore'));
        $this->assertSame('{"01001":"RevCore","01002":"RevCore"}', $save('01002', 'RevCore'));
        // "default" (or no pack) removes the preference; no preference at all is null
        $this->assertSame('{"01002":"RevCore"}', $save('01001', 'default'));
        $this->assertNull($save('01002', ''));
    }

    public function testArtPreferenceWithoutCard() {
        $client = $this->createAuthenticatedClient();
        $client->request('POST', '/collection/art/save', ['pack_code' => 'RevCore']);

        $this->assertSame(400, $client->getResponse()->getStatusCode());
        $this->assertSame('{"success":false,"error":"missing card_code"}', $client->getResponse()->getContent());
    }

    /* ------------------------------------------------------- custom packs */

    public function testCreateEditAndDeleteACustomPack() {
        $client = $this->createAuthenticatedClient();

        // 1. create: invalid entries are skipped (unknown card, duplicate, quantity out of 1..9)
        $response = $this->submitPackForm($client, '/collection/custom-pack/new', 'PHPUnit Pack', [
            ['card_code' => '01001', 'quantity' => 2],
            ['card_code' => '01013', 'quantity' => 3],
            ['card_code' => '01001', 'quantity' => 1],
            ['card_code' => '99999', 'quantity' => 1],
            ['card_code' => '01014', 'quantity' => 10],
            ['card_code' => '01015', 'quantity' => 0],
            ['card_code' => '01016'],
        ]);
        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame('/collection/packs', $response->headers->get('Location'));
        $id = (int) $this->db($client)->fetchColumn('SELECT MAX(id) FROM user_custom_pack');
        $this->assertGreaterThan($this->maxIds['user_custom_pack'], $id);
        $pack = $this->fetchPack($client, $id);
        $this->assertRegExp("/^custom_{$id}_[0-9a-f]{6}$/", $pack['code']);
        $this->assertSame(['PHPUnit Pack', '1', '0', 'test'], [$pack['name'], $pack['is_enabled'], $pack['is_published'], $pack['username']]);
        $this->assertSame(['01001' => 2, '01013' => 3, '01016' => 1], $this->packCards($client, $id));
        // flash messages are displayed by JavaScript: app.ui.insert_alert_message('success', <JSON>)
        $client->followRedirect();
        $this->assertContains("insert_alert_message('success', " . json_encode('Custom pack "PHPUnit Pack" created.') . ')', $client->getResponse()->getContent());

        // 2. edit: the cards are replaced
        $crawler = $client->request('GET', "/collection/custom-pack/$id/edit");
        $this->assertSame('PHPUnit Pack', $crawler->filter('#pack-name')->attr('value'));
        $response = $this->submitPackForm($client, "/collection/custom-pack/$id/edit", 'PHPUnit Pack Edited', [
            ['card_code' => '01002', 'quantity' => 1],
        ]);
        $this->assertSame('/collection/packs', $response->headers->get('Location'));
        $this->assertSame('PHPUnit Pack Edited', $this->fetchPack($client, $id)['name']);
        $this->assertSame(['01002' => 1], $this->packCards($client, $id));

        // 3. toggle enabled and published (buttons of the collection page)
        $client->request('POST', "/collection/custom-pack/$id/toggle");
        $client->request('POST', "/collection/custom-pack/$id/publish");
        $this->assertSame('/collection/packs', $client->getResponse()->headers->get('Location'));
        $pack = $this->fetchPack($client, $id);
        $this->assertSame(['0', '1'], [$pack['is_enabled'], $pack['is_published']]);

        // 4. delete, with its cards
        $client->request('POST', "/collection/custom-pack/$id/delete");
        $this->assertSame('/collection/packs', $client->getResponse()->headers->get('Location'));
        $this->assertFalse($this->fetchPack($client, $id));
        $this->assertSame([], $this->packCards($client, $id));
    }

    public function testCustomPackNameIsRequired() {
        $client = $this->createAuthenticatedClient();

        $response = $this->submitPackForm($client, '/collection/custom-pack/new', '  ', [['card_code' => '01001', 'quantity' => 1]]);
        $this->assertSame('/collection/custom-pack/new', $response->headers->get('Location'));
        $this->assertSame((string) $this->maxIds['user_custom_pack'], $this->db($client)->fetchColumn('SELECT MAX(id) FROM user_custom_pack'));

        $response = $this->submitPackForm($client, '/collection/custom-pack/1/edit', '', []);
        $this->assertSame('/collection/custom-pack/1/edit', $response->headers->get('Location'));
        $crawler = $client->followRedirect();
        $this->assertContains('Pack name is required.', $crawler->filter('body')->text());
        $this->assertSame('Test Custom Pack', $this->fetchPack($client, 1)['name']);
    }

    /**
     * @dataProvider foreignPackRouteProvider
     */
    public function testCannotChangeAnotherUsersPack($method, $uri) {
        $client = $this->createAuthenticatedClient('admin');
        $client->request($method, $uri, ['name' => 'Hacked', 'cards_json' => '[]']);

        $this->assertSame(404, $client->getResponse()->getStatusCode());
        $this->assertSame(['Test Custom Pack', '1', '1', 'test'], array_values(array_diff_key($this->fetchPack($client, 1), ['code' => 0])));
        $this->assertSame(['01001' => 1, '01016' => 3], $this->packCards($client, 1));
    }

    public function foreignPackRouteProvider() {
        return [
            'edit form' => ['GET', '/collection/custom-pack/1/edit'],
            'update' => ['POST', '/collection/custom-pack/1/update'],
            'toggle' => ['POST', '/collection/custom-pack/1/toggle'],
            'publish' => ['POST', '/collection/custom-pack/1/publish'],
            'delete' => ['POST', '/collection/custom-pack/1/delete'],
        ];
    }

    public function testCopyAPublishedPack() {
        $client = $this->createAuthenticatedClient('admin');
        $client->request('POST', '/collection/custom-pack/1/copy', [], [], ['HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest']);

        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $this->assertSame(['success' => true, 'name' => 'Test Custom Pack'], json_decode($client->getResponse()->getContent(), true));
        $id = (int) $this->db($client)->fetchColumn('SELECT MAX(id) FROM user_custom_pack');
        $copy = $this->fetchPack($client, $id);
        $this->assertSame(['Test Custom Pack', '1', '0', 'admin'], [$copy['name'], $copy['is_enabled'], $copy['is_published'], $copy['username']]);
        $this->assertNotSame($this->fixturePack[0]['code'], $copy['code']);
        $this->assertSame(['01001' => 1, '01016' => 3], $this->packCards($client, $id));
    }

    public function testCopyAnUnpublishedPack() {
        $client = $this->createAuthenticatedClient('admin');
        $this->db($client)->update('user_custom_pack', ['is_published' => 0], ['id' => 1]);
        $client->request('POST', '/collection/custom-pack/1/copy');

        $this->assertSame(404, $client->getResponse()->getStatusCode());
        $this->assertSame('{"error":"Pack not found"}', $client->getResponse()->getContent());
        $this->assertSame((string) $this->maxIds['user_custom_pack'], $this->db($client)->fetchColumn('SELECT MAX(id) FROM user_custom_pack'));
    }

    /**
     * @dataProvider anonymousRouteProvider
     */
    public function testAnonymousIsRedirectedToLogin($uri) {
        $client = static::createClient();
        $client->request('POST', $uri, ['selected-packs' => '1', 'card_code' => '01001', 'name' => 'Anonymous']);

        $this->assertSame(302, $client->getResponse()->getStatusCode());
        $this->assertSame('http://localhost/login', $client->getResponse()->headers->get('Location'));
    }

    public function anonymousRouteProvider() {
        return [
            'owned packs' => ['/collection/packs/save'],
            'art preference' => ['/collection/art/save'],
            'new custom pack' => ['/collection/custom-pack/save'],
            'copy' => ['/collection/custom-pack/1/copy'],
        ];
    }
}
