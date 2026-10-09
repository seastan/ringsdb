<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Admin area, write forms, as the fixture "admin" (ROLE_ADMIN):
 * - the generated CRUD of the reference data (cycle, pack, type, sphere, card, card printing,
 *   scenario, encounter): create -> show, edit -> edit, delete -> list, through the real forms
 *   (CSRF tokens included). Only records created by the test are changed;
 * - user search and moderation (comments, decklists).
 *
 * Everything created or changed here is removed or restored in tearDown().
 * Not covered: the card image upload (writes into web/), the scenario import command and the
 * Excel / CSV imports.
 */
class AdminWriteTest extends WebTestCase
{
    use \App\Tests\LocationTrait;

    /** tables of the reference data, in an order that respects the foreign keys when deleting */
    public const TABLES = ['card_printing', 'scenario_encounter', 'scenario', 'encounter', 'card', 'pack', 'cycle', 'type', 'sphere'];

    private KernelBrowser $client;

    /** @var int[] */
    private array $maxIds = [];

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $connection = $this->db();
        foreach (array_merge(self::TABLES, ['comment', 'decklist', 'deck']) as $table) {
            $this->maxIds[$table] = 'scenario_encounter' === $table ? 0 : (int) $connection->fetchOne("SELECT MAX(id) FROM $table");
        }
    }

    protected function tearDown(): void
    {
        $connection = $this->db();
        $max = $this->maxIds;
        $connection->executeStatement("DELETE FROM scenario_encounter WHERE scenario_id > {$max['scenario']} OR encounter_id > {$max['encounter']}");
        foreach (['decklist_spheres', 'decklistslot', 'decklistsideslot'] as $table) {
            $connection->executeStatement("DELETE FROM $table WHERE decklist_id > {$max['decklist']}");
        }

        $connection->executeStatement("DELETE FROM decklist WHERE id > {$max['decklist']}");
        foreach (['deckslot', 'decksideslot'] as $table) {
            $connection->executeStatement("DELETE FROM $table WHERE deck_id > {$max['deck']} OR card_id > {$max['card']}");
        }

        $connection->executeStatement("DELETE FROM deck WHERE id > {$max['deck']}");
        foreach (self::TABLES as $table) {
            if ('scenario_encounter' !== $table) {
                $connection->executeStatement("DELETE FROM $table WHERE id > {$max[$table]}");
            }
        }

        $connection->executeStatement("DELETE FROM comment WHERE id > {$max['comment']}");
        $connection->update('comment', ['is_hidden' => 0], ['id' => 1]);
        parent::tearDown();
    }

    /* ------------------------------------------------------------ helpers */

    private function db(): \Doctrine\DBAL\Connection
    {
        return static::getContainer()->get('doctrine')->getConnection();
    }

    private function createAdminClient(): KernelBrowser
    {
        $client = $this->client;
        $crawler = $client->request('GET', '/login');
        $client->submit($crawler->selectButton('_submit')->form(['_username' => 'admin', '_password' => 'admin']));
        $this->assertTrue($client->getResponse()->isRedirect(), 'Login as admin failed');

        return $client;
    }

    /**
     * @param array<string, mixed> $values
     */
    private function submitForm(KernelBrowser $client, string $pageUri, string $action, array $values): \Symfony\Component\HttpFoundation\Response
    {
        $crawler = $client->request('GET', $pageUri);
        $this->assertSame(200, $client->getResponse()->getStatusCode(), "GET $pageUri");
        $client->submit($crawler->filter("form[action=\"$action\"]")->form(), $values);

        return $client->getResponse();
    }

    /**
     * @param array<string, string> $values
     *
     * @return array<string, mixed>
     */
    private function prefixed(string $prefix, array $values): array
    {
        $fields = [];
        foreach ($values as $name => $value) {
            $fields[$prefix.'['.str_replace('.', '][', $name).']'] = $value;
        }

        return $fields;
    }

    /* -------------------------------------------------------------- CRUD */
    /**
     * [route slug, form name, table, created values, expected columns, updated values, expected columns].
     *
     * @return array<string, string[]|array<string, bool|string>[]|array<string, string>[]>
     */
    public static function crudProvider(): array
    {
        return [
            'cycle' => ['cycle', 'appbundle_cycletype', 'cycle',
                ['code' => 'PHPU', 'name' => 'PHPUnit Cycle', 'position' => '99', 'isBox' => true],
                ['code' => 'PHPU', 'name' => 'PHPUnit Cycle', 'position' => '99', 'is_box' => '1', 'is_saga' => '0'],
                ['name' => 'PHPUnit Cycle Edited', 'isBox' => false, 'isSaga' => true],
                ['code' => 'PHPU', 'name' => 'PHPUnit Cycle Edited', 'position' => '99', 'is_box' => '0', 'is_saga' => '1'],
            ],
            'pack' => ['pack', 'appbundle_packtype', 'pack',
                ['code' => 'PHPU', 'name' => 'PHPUnit Pack', 'dateRelease.year' => '2020', 'dateRelease.month' => '5', 'dateRelease.day' => '17', 'size' => '60', 'cycle' => '1', 'position' => '99'],
                ['code' => 'PHPU', 'name' => 'PHPUnit Pack', 'date_release' => '2020-05-17', 'size' => '60', 'cycle_id' => '1', 'position' => '99'],
                ['name' => 'PHPUnit Pack Edited', 'size' => '10'],
                ['code' => 'PHPU', 'name' => 'PHPUnit Pack Edited', 'date_release' => '2020-05-17', 'size' => '10', 'cycle_id' => '1', 'position' => '99'],
            ],
            'type' => ['type', 'appbundle_type', 'type',
                ['code' => 'phpunit', 'name' => 'PHPUnit Type'],
                ['code' => 'phpunit', 'name' => 'PHPUnit Type'],
                ['name' => 'PHPUnit Type Edited'],
                ['code' => 'phpunit', 'name' => 'PHPUnit Type Edited'],
            ],
            'sphere' => ['sphere', 'appbundle_sphere', 'sphere',
                ['code' => 'phpunit', 'name' => 'PHPUnit Sphere', 'is_primary' => true],
                ['code' => 'phpunit', 'name' => 'PHPUnit Sphere', 'is_primary' => '1'],
                ['name' => 'PHPUnit Sphere Edited', 'is_primary' => false],
                ['code' => 'phpunit', 'name' => 'PHPUnit Sphere Edited', 'is_primary' => '0'],
            ],
            'card' => ['card', 'appbundle_cardtype', 'card',
                ['position' => '1', 'deck_limit' => '3', 'code' => '99901', 'type' => '2', 'sphere' => '1', 'name' => 'PHPUnit Card', 'traits' => 'Test.', 'text' => 'Does *nothing*.', 'cost' => '2', 'willpower' => '1', 'attack' => '1', 'defense' => '1', 'health' => '2', 'is_unique' => true],
                ['code' => '99901', 'name' => 'PHPUnit Card', 'type_id' => '2', 'sphere_id' => '1', 'deck_limit' => '3', 'traits' => 'Test.', 'text' => 'Does *nothing*.', 'cost' => '2', 'is_unique' => '1', 'has_errata' => '0'],
                ['name' => 'PHPUnit Card Edited', 'cost' => '3', 'has_errata' => true],
                ['code' => '99901', 'name' => 'PHPUnit Card Edited', 'type_id' => '2', 'sphere_id' => '1', 'deck_limit' => '3', 'traits' => 'Test.', 'text' => 'Does *nothing*.', 'cost' => '3', 'is_unique' => '1', 'has_errata' => '1'],
            ],
            'card printing' => ['card-printing', 'appbundle_cardprintingtype', 'card_printing',
                ['card' => '1', 'pack' => '2', 'position' => '999', 'quantity' => '1', 'imageCode' => '99999', 'illustrator' => 'PHPUnit Artist'],
                ['card_id' => '1', 'pack_id' => '2', 'position' => '999', 'quantity' => '1', 'image_code' => '99999', 'illustrator' => 'PHPUnit Artist'],
                ['quantity' => '2', 'illustrator' => 'PHPUnit Artist Edited'],
                ['card_id' => '1', 'pack_id' => '2', 'position' => '999', 'quantity' => '2', 'image_code' => '99999', 'illustrator' => 'PHPUnit Artist Edited'],
            ],
            'encounter' => ['encounter', 'appbundle_encounter', 'encounter',
                ['code' => 'PHPUnit Encounter', 'name' => 'PHPUnit Encounter', 'pack' => '1'],
                ['code' => 'PHPUnit Encounter', 'name' => 'PHPUnit Encounter', 'pack_id' => '1'],
                ['name' => 'PHPUnit Encounter Edited', 'pack' => '2'],
                ['code' => 'PHPUnit Encounter', 'name' => 'PHPUnit Encounter Edited', 'pack_id' => '2'],
            ],
            'scenario' => ['scenario', 'appbundle_scenario', 'scenario',
                ['code' => 'PHPUnit Scenario', 'name' => 'PHPUnit Scenario', 'position' => '999', 'pack' => '1'],
                ['code' => 'PHPUnit Scenario', 'name' => 'PHPUnit Scenario', 'position' => '999', 'pack_id' => '1', 'has_easy' => '1', 'has_nightmare' => '0'],
                ['name' => 'PHPUnit Scenario Edited'],
                ['code' => 'PHPUnit Scenario', 'name' => 'PHPUnit Scenario Edited', 'position' => '999', 'pack_id' => '1', 'has_easy' => '1', 'has_nightmare' => '0'],
            ],
        ];
    }

    #[DataProvider('crudProvider')]
    public function testCreateEditDelete(string $slug, string $formName, string $table, array $created, array $expectedCreated, array $updated, array $expectedUpdated): void
    {
        $client = $this->createAdminClient();
        $columns = implode(', ', array_keys($expectedCreated));

        // create: redirect to the show page
        $response = $this->submitForm($client, "/admin/$slug/new", "/admin/$slug/create", $this->prefixed($formName, $created));
        $this->assertSame(302, $response->getStatusCode());
        $this->assertMatchesRegularExpression("#^/admin/$slug/\\d+/show$#", self::location($response));
        $id = (int) explode('/', self::location($response))[3];
        $this->assertGreaterThan($this->maxIds[$table], $id);
        $this->assertEquals($expectedCreated, $this->db()->fetchAssociative("SELECT $columns FROM $table WHERE id = ?", [$id]));

        $client->followRedirect();
        $this->assertSame(200, $client->getResponse()->getStatusCode());

        // edit: redirect to the edit page
        $response = $this->submitForm($client, "/admin/$slug/$id/edit", "/admin/$slug/$id/update", $this->prefixed($formName, $updated));
        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame("/admin/$slug/$id/edit", $response->headers->get('Location'));
        $this->assertEquals($expectedUpdated, $this->db()->fetchAssociative("SELECT $columns FROM $table WHERE id = ?", [$id]));

        // delete: redirect to the list
        $response = $this->submitForm($client, "/admin/$slug/$id/edit", "/admin/$slug/$id/delete", []);
        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame("/admin/$slug/", $response->headers->get('Location'));
        $this->assertSame(0, $this->db()->fetchOne("SELECT COUNT(*) FROM $table WHERE id = ?", [$id]));
    }

    public function testScenarioEncounters(): void
    {
        $client = $this->createAdminClient();
        $crawler = $client->request('GET', '/admin/scenario/new');
        $form = $crawler->filter('form[action="/admin/scenario/create"]')->form($this->prefixed('appbundle_scenario', [
            'code' => 'PHPUnit Scenario', 'name' => 'PHPUnit Scenario', 'position' => '999', 'pack' => '1',
        ]));
        /** @var \Symfony\Component\DomCrawler\Field\ChoiceFormField[] $encounters */
        $encounters = $form['appbundle_scenario[encounters]'];
        $encounters[0]->tick();
        $encounters[2]->tick();
        $client->submit($form);

        $id = (int) explode('/', self::location($client->getResponse()))[3];
        $encounters = $this->db()->fetchAllAssociative('SELECT encounter_id FROM scenario_encounter WHERE scenario_id = ? ORDER BY encounter_id', [$id]);
        $this->assertSame([1, 3], array_column($encounters, 'encounter_id'));
    }

    /**
     * The delete button of a pack's page used to post to the cycle delete route: the delete forms
     * share their CSRF token, so it deleted the cycle with the same id.
     */
    public function testPackPageDeletesThePack(): void
    {
        $client = $this->createAdminClient();
        $crawler = $client->request('GET', '/admin/pack/1/show');

        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $this->assertCount(1, $crawler->filter('form[action="/admin/pack/1/delete"]'));
        $this->assertCount(0, $crawler->filter('form[action^="/admin/cycle/"]'));
    }

    public function testDeleteRequiresTheFormToken(): void
    {
        $client = $this->createAdminClient();
        $client->request('POST', '/admin/type/1/delete', []);

        // the delete form is invalid without its CSRF token: nothing is deleted
        $this->assertSame(302, $client->getResponse()->getStatusCode());
        $this->assertSame('/admin/type/', $client->getResponse()->headers->get('Location'));
        $this->assertSame(1, $this->db()->fetchOne('SELECT COUNT(*) FROM type WHERE id = 1'));
    }

    /**
     * A card used in decks and decklists cannot be deleted, but can be "force deleted": its
     * slots, printings and reviews (with their votes and comments) are deleted with it.
     */
    public function testForceDeleteACard(): void
    {
        $client = $this->createAdminClient();
        $response = $this->submitForm($client, '/admin/card/new', '/admin/card/create', $this->prefixed('appbundle_cardtype', [
            'position' => '1', 'deck_limit' => '3', 'code' => '99901', 'type' => '2', 'sphere' => '1', 'name' => 'PHPUnit Card',
        ]));
        $cardId = (int) explode('/', self::location($response))[3];
        $connection = $this->db();
        $connection->insert('card_printing', ['card_id' => $cardId, 'pack_id' => 1, 'position' => 999, 'quantity' => 1, 'image_code' => '99901', 'date_creation' => '2015-08-16 00:00:00', 'date_update' => '2015-08-16 00:00:00']);
        $connection->insert('deckslot', ['deck_id' => 1, 'card_id' => $cardId, 'quantity' => 1]);
        $connection->insert('decklistslot', ['decklist_id' => 1, 'card_id' => $cardId, 'quantity' => 1]);
        $connection->insert('review', ['card_id' => $cardId, 'user_id' => 2, 'date_creation' => '2015-08-16 00:00:00', 'date_update' => '2015-08-16 00:00:00', 'text_md' => 'Review', 'text_html' => '<p>Review</p>', 'nb_votes' => 1]);

        $reviewId = (int) $connection->lastInsertId();
        $connection->insert('reviewvote', ['review_id' => $reviewId, 'user_id' => 3]);
        $connection->insert('reviewcomment', ['review_id' => $reviewId, 'user_id' => 3, 'date_creation' => '2015-08-16 00:00:00', 'date_update' => '2015-08-16 00:00:00', 'text' => 'Comment']);

        // regular delete fails on the foreign keys
        $response = $this->submitForm($client, "/admin/card/$cardId/edit", "/admin/card/$cardId/delete", []);
        $this->assertSame(500, $response->getStatusCode());
        $this->assertSame(1, $connection->fetchOne('SELECT COUNT(*) FROM card WHERE id = ?', [$cardId]));

        $response = $this->submitForm($client, "/admin/card/$cardId/edit", "/admin/card/$cardId/force_delete", []);
        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame('/admin/card/', $response->headers->get('Location'));
        $this->assertSame(0, $connection->fetchOne('SELECT COUNT(*) FROM card WHERE id = ?', [$cardId]));
        $this->assertSame(0, $connection->fetchOne('SELECT COUNT(*) FROM card_printing WHERE card_id = ?', [$cardId]));
        $this->assertSame(0, $connection->fetchOne('SELECT COUNT(*) FROM deckslot WHERE card_id = ?', [$cardId]));
        $this->assertSame(0, $connection->fetchOne('SELECT COUNT(*) FROM decklistslot WHERE card_id = ?', [$cardId]));
        $this->assertSame(0, $connection->fetchOne('SELECT COUNT(*) FROM review WHERE card_id = ?', [$cardId]));
        $this->assertSame(0, $connection->fetchOne('SELECT COUNT(*) FROM reviewvote WHERE review_id = ?', [$reviewId]));
        $this->assertSame(0, $connection->fetchOne('SELECT COUNT(*) FROM reviewcomment WHERE review_id = ?', [$reviewId]));
        $this->assertSame(22, $connection->fetchOne('SELECT COUNT(*) FROM deckslot WHERE deck_id = 1'));
    }

    /**
     * Reference data still in use cannot be deleted (foreign keys): 500, nothing is deleted.
     */
    public function testReferenceDataInUseCannotBeDeleted(): void
    {
        $client = $this->createAdminClient();
        $response = $this->submitForm($client, '/admin/cycle/1/edit', '/admin/cycle/1/delete', []);

        $this->assertSame(500, $response->getStatusCode());
        $this->assertSame(1, $this->db()->fetchOne('SELECT COUNT(*) FROM cycle WHERE id = 1'));
    }

    /* -------------------------------------------------------- moderation */
    #[DataProvider('findUserProvider')]
    public function testFindUser(array $values, string $location): void
    {
        $client = $this->createAdminClient();
        $client->request('POST', '/admin/user/find_process', $values);

        $this->assertSame(302, $client->getResponse()->getStatusCode());
        $this->assertSame($location, $client->getResponse()->headers->get('Location'));
    }

    /**
     * @return array<string, string[]|array<string, string>[]>
     */
    public static function findUserProvider(): array
    {
        return [
            'by username' => [['username' => 'test'], '/admin/user/show/1'],
            'by id' => [['id' => '2'], '/admin/user/show/2'],
            'unknown' => [['username' => 'nobody'], '/admin/user/find'],
        ];
    }

    public function testToggleAndDeleteAComment(): void
    {
        $client = $this->createAdminClient();

        $client->request('GET', '/admin/comment/toggle_hidden/1');
        $this->assertSame('/admin/user/comments/1', $client->getResponse()->headers->get('Location'));
        $this->assertSame(1, $this->db()->fetchOne('SELECT is_hidden FROM comment WHERE id = 1'));
        $client->request('GET', '/admin/comment/toggle_hidden/1');
        $this->assertSame(0, $this->db()->fetchOne('SELECT is_hidden FROM comment WHERE id = 1'));

        // delete a comment created for the test
        $this->db()->insert('comment', ['decklist_id' => 1, 'user_id' => 2, 'text' => 'Spam', 'date_creation' => '2015-08-16 00:00:00', 'is_hidden' => 0]);
        $commentId = (int) $this->db()->lastInsertId();
        $client->request('GET', "/admin/comment/delete/$commentId");
        $this->assertSame('/admin/user/comments/2', $client->getResponse()->headers->get('Location'));
        $this->assertSame(0, $this->db()->fetchOne('SELECT COUNT(*) FROM comment WHERE id = ?', [$commentId]));
    }

    /**
     * Deleting a decklist unlinks its successors and the decks copied from it.
     */
    public function testDeleteADecklist(): void
    {
        $client = $this->createAdminClient();
        $connection = $this->db();
        // a copy of decklist 1, derived from it, with a deck copied from the copy
        $row = $connection->fetchAssociative('SELECT * FROM decklist WHERE id = 1');
        $this->assertNotFalse($row);
        unset($row['id']);
        $connection->insert('decklist', ['name' => 'PHPUnit Copy', 'precedent_decklist_id' => null] + $row);
        $copyId = (int) $connection->lastInsertId();
        $connection->insert('decklist', ['name' => 'PHPUnit Successor', 'precedent_decklist_id' => $copyId] + $row);
        $successorId = (int) $connection->lastInsertId();
        $deck = $connection->fetchAssociative('SELECT * FROM deck WHERE id = 2');
        $this->assertNotFalse($deck);
        unset($deck['id']);
        $connection->insert('deck', ['name' => 'PHPUnit Child', 'parent_decklist_id' => $copyId] + $deck);
        $childId = (int) $connection->lastInsertId();

        $client->request('GET', "/admin/decklist/delete/$copyId");

        $this->assertSame(302, $client->getResponse()->getStatusCode());
        $this->assertSame('/admin/user/decklists/1', $client->getResponse()->headers->get('Location'));
        $this->assertSame(0, $connection->fetchOne('SELECT COUNT(*) FROM decklist WHERE id = ?', [$copyId]));
        $this->assertNull($connection->fetchOne('SELECT precedent_decklist_id FROM decklist WHERE id = ?', [$successorId]));
        $this->assertNull($connection->fetchOne('SELECT parent_decklist_id FROM deck WHERE id = ?', [$childId]));
    }
}
