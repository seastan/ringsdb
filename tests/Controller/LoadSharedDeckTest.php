<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * The deck endpoint of DragnCards (/api/oauth2/deck/load/{id}): anonymous, CORS-open, and gated
 * by the owner's "Share my decks" setting.
 *
 * Fixtures: "test" owns deck 1 and does not share their decks.
 */
class LoadSharedDeckTest extends WebTestCase
{
    use JsonSnapshotTrait;

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

    /**
     * @return array<string, mixed>
     */
    private function load(string $id)
    {
        $this->client->request('GET', '/api/oauth2/deck/load/'.$id);
        $response = $this->client->getResponse();

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('application/json', $response->headers->get('Content-Type'));
        $this->assertSame('*', $response->headers->get('Access-Control-Allow-Origin'));

        return json_decode((string) $response->getContent(), true);
    }

    public function testSharedDeck(): void
    {
        $this->client->getContainer()->get('doctrine')->getConnection()
            ->update('user', ['is_share_decks' => 1], ['username' => 'test']);

        $this->load('1');
        // the same serialization as the private API
        $this->assertMatchesJsonSnapshot('private/deck_1', $this->client->getResponse()->getContent());
    }

    public function testDeckNotShared(): void
    {
        $this->assertSame([
            'success' => false,
            'error' => 'You are not allowed to view this deck. To get access, you can ask the deck owner to enable "Share my decks" on their account.',
        ], $this->load('1'));
    }

    public function testUnknownDeck(): void
    {
        $this->assertSame(['success' => false, 'error' => 'Deck not found.'], $this->load('999'));
    }

    public function testNonNumericIdIsNotFound(): void
    {
        $this->client->request('GET', '/api/oauth2/deck/load/abc');
        $this->assertSame(404, $this->client->getResponse()->getStatusCode());
    }
}
