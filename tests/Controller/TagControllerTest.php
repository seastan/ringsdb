<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Deck tags (TagController): add / remove / clear tags on a selection of decks, from the My Decks
 * page (ui.decks.js posts "ids" and "tags" with AJAX, and updates the page with the "tags" of
 * the JSON answer).
 *
 * Fixture decks of "test": 1 "tactics leadership lore", 2 "leadership spirit", 3 "spirit lore",
 * 4 "tactics". Their tags and dates are restored in tearDown().
 */
class TagControllerTest extends WebTestCase
{
    private KernelBrowser $client;

    /** @var array */
    private $fixtureDecks;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->fixtureDecks = $this->db($this->client)->fetchAll('SELECT id, user_id, tags, date_update FROM deck ORDER BY id');
    }

    protected function tearDown(): void
    {
        $connection = $this->db($this->client);
        foreach ($this->fixtureDecks as $deck) {
            $connection->update('deck', $deck, ['id' => $deck['id']]);
        }
        parent::tearDown();
    }

    /* ------------------------------------------------------------ helpers */

    /**
     * @return \Doctrine\DBAL\Connection
     */
    private function db()
    {
        return static::getContainer()->get('doctrine')->getConnection();
    }

    /**
     * @param string $username
     *
     * @return KernelBrowser
     */
    private function createAuthenticatedClient($username = 'test')
    {
        $client = $this->client;
        $crawler = $client->request('GET', '/login');
        $client->submit($crawler->selectButton('_submit')->form(['_username' => $username, '_password' => $username]));
        $this->assertTrue($client->getResponse()->isRedirect(), "Login as $username failed");

        return $client;
    }

    /**
     * @return array the decoded JSON answer
     */
    private function post(KernelBrowser $client, $action, array $parameters)
    {
        $client->request('POST', "/tag/$action", $parameters, [], ['HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest']);
        $this->assertSame(200, $client->getResponse()->getStatusCode());

        return json_decode($client->getResponse()->getContent(), true);
    }

    /**
     * @return array<int|string, mixed>
     */
    private function tags(KernelBrowser $client)
    {
        return array_column($this->db($client)->fetchAll('SELECT id, tags FROM deck ORDER BY id'), 'tags', 'id');
    }

    /* -------------------------------------------------------------- tests */

    public function testAddTags(): void
    {
        $client = $this->createAuthenticatedClient();

        $answer = $this->post($client, 'add', ['ids' => ['1', '4'], 'tags' => ['dwarf', 'tactics']]);

        // tags already present are not duplicated
        $this->assertSame(['success' => true, 'tags' => [
            1 => ['tactics', 'leadership', 'lore', 'dwarf'],
            4 => ['tactics', 'dwarf'],
        ]], $answer);
        $this->assertSame(['1' => 'tactics leadership lore dwarf', '2' => 'leadership spirit', '3' => 'spirit lore', '4' => 'tactics dwarf'], $this->tags($client));
        // not JSON for the Content-Type (the JavaScript asks for dataType: 'json')
        $this->assertSame('text/html; charset=UTF-8', $client->getResponse()->headers->get('Content-Type'));
    }

    public function testAddTagsToADeckWithoutTags(): void
    {
        $client = $this->createAuthenticatedClient();
        $this->db($client)->update('deck', ['tags' => ''], ['id' => 2]);

        $answer = $this->post($client, 'add', ['ids' => ['2'], 'tags' => ['gondor']]);

        $this->assertSame([2 => ['gondor']], $answer['tags']);
        $this->assertSame('gondor', $this->tags($client)['2']);
    }

    /**
     * The page splits the typed text on spaces, so it can send empty tags: they are ignored, and
     * the stored tags are cleaned up.
     */
    public function testEmptyAndSpacedTagsAreIgnored(): void
    {
        $client = $this->createAuthenticatedClient();
        $this->db($client)->update('deck', ['tags' => ' tactics  lore '], ['id' => 4]);

        $answer = $this->post($client, 'add', ['ids' => ['4'], 'tags' => ['', 'dwarf', '', ' two  words ']]);
        $this->assertSame([4 => ['tactics', 'lore', 'dwarf', 'two', 'words']], $answer['tags']);
        $this->assertSame('tactics lore dwarf two words', $this->tags($client)['4']);

        $this->db($client)->update('deck', ['tags' => ' tactics  lore '], ['id' => 4]);
        $answer = $this->post($client, 'remove', ['ids' => ['4'], 'tags' => ['', 'lore']]);
        $this->assertSame([4 => ['tactics']], $answer['tags']);
        $this->assertSame('tactics', $this->tags($client)['4']);
    }

    public function testRemoveTags(): void
    {
        $client = $this->createAuthenticatedClient();

        $answer = $this->post($client, 'remove', ['ids' => ['1', '2', '3'], 'tags' => ['lore', 'unknown']]);

        $this->assertSame(['success' => true, 'tags' => [
            1 => ['tactics', 'leadership'],
            2 => ['leadership', 'spirit'],
            3 => ['spirit'],
        ]], $answer);
        $this->assertSame(['1' => 'tactics leadership', '2' => 'leadership spirit', '3' => 'spirit', '4' => 'tactics'], $this->tags($client));
    }

    public function testClearTags(): void
    {
        $client = $this->createAuthenticatedClient();

        $answer = $this->post($client, 'clear', ['ids' => ['1', '3']]);

        $this->assertSame(['success' => true, 'tags' => [1 => [], 3 => []]], $answer);
        $this->assertSame(['1' => '', '2' => 'leadership spirit', '3' => '', '4' => 'tactics'], $this->tags($client));
    }

    /**
     * Unknown decks and other users' decks are skipped silently.
     *
     * @dataProvider actionProvider
     */
    public function testForeignAndUnknownDecksAreSkipped($action): void
    {
        $client = $this->createAuthenticatedClient('admin');

        $answer = $this->post($client, $action, ['ids' => ['1', '999'], 'tags' => ['hacked']]);

        $this->assertSame(['success' => true], $answer);
        $this->assertSame(array_column($this->fixtureDecks, 'tags', 'id'), $this->tags($client));
    }

    /**
     * @return array
     */
    public function actionProvider()
    {
        return ['add' => ['add'], 'remove' => ['remove'], 'clear' => ['clear']];
    }

    /**
     * The JavaScript sends AJAX requests: an anonymous one gets a 403 JSON answer (built by
     * CoreExceptionListener). Nothing is changed.
     *
     * @dataProvider actionProvider
     */
    public function testAnonymousAjaxIsDenied($action): void
    {
        $client = $this->client;
        $client->request('POST', "/tag/$action", ['ids' => ['1'], 'tags' => ['hacked']], [], ['HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest']);

        $this->assertSame(403, $client->getResponse()->getStatusCode());
        $this->assertSame(['success' => false, 'message' => 'Access Denied.'], json_decode($client->getResponse()->getContent(), true));
        $this->assertSame(array_column($this->fixtureDecks, 'tags', 'id'), $this->tags($client));
    }

    /**
     * @dataProvider actionProvider
     */
    public function testAnonymousIsRedirectedToLogin($action): void
    {
        $client = $this->client;
        $client->request('POST', "/tag/$action", ['ids' => ['1'], 'tags' => ['hacked']]);

        $this->assertSame(302, $client->getResponse()->getStatusCode());
        $this->assertSame('http://localhost/login', $client->getResponse()->headers->get('Location'));
        $this->assertSame(array_column($this->fixtureDecks, 'tags', 'id'), $this->tags($client));
    }

    public function testGetIsNotAllowed(): void
    {
        $client = $this->createAuthenticatedClient();
        $client->request('GET', '/tag/add');

        $this->assertSame(405, $client->getResponse()->getStatusCode());
    }
}
