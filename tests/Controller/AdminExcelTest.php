<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Excel export / import of the cards (ExcelController, admin only): the tests download a pack,
 * change the file with PhpSpreadsheet, and upload it back.
 *
 * - the download is a StreamedResponse, sent by Symfony's StreamedResponseListener as soon as
 *   the controller returns it: it is captured with an output buffer around the request;
 * - the upload echoes an HTML report of the changes before returning its response: also
 *   captured.
 *
 * The cards of the Core Set, and any card created, are restored / deleted in tearDown().
 */
class AdminExcelTest extends WebTestCase
{
    use \App\Tests\TemporaryFileTrait;

    private KernelBrowser $client;

    public const HEADER = ['type', 'sphere', 'position', 'code', 'name', 'traits', 'text', 'flavor', 'isUnique', 'cost', 'threat',
        'willpower', 'attack', 'defense', 'health', 'victory', 'quest', 'deckLimit', 'hasErrata'];

    private int $maxCardId;

    private array $coreCards;

    /** @var string[] */
    private array $files = [];

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $connection = $this->db();
        $this->maxCardId = (int) $connection->fetchColumn('SELECT MAX(id) FROM card');
        $this->coreCards = $connection->fetchAll('SELECT c.* FROM card c JOIN card_printing cp ON cp.card_id = c.id WHERE cp.pack_id = 1');
    }

    protected function tearDown(): void
    {
        $connection = $this->db();
        $connection->exec("DELETE FROM card WHERE id > {$this->maxCardId}");
        foreach ($this->coreCards as $card) {
            $connection->update('card', $card, ['id' => $card['id']]);
        }

        foreach ($this->files as $file) {
            @unlink($file);
        }

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
     * Downloads the cards of a pack (0 = all the cards), returns the path of the saved file.
     */
    private function download(KernelBrowser $client, int $packId): string
    {
        ob_start();
        $client->request('POST', '/admin/excel/download', ['pack' => $packId]);
        $content = ob_get_clean();

        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $file = self::temporaryFile('excel').'.xlsx';
        $this->files[] = $file;
        file_put_contents($file, $content);

        return $file;
    }

    /**
     * @param array<string, string> $parameters
     *
     * @return array<int, string|bool> [response content, echoed report]
     */
    private function upload(KernelBrowser $client, string $file, array $parameters = []): array
    {
        ob_start();
        $client->request('POST', '/admin/excel/upload', $parameters, ['upfile' => new UploadedFile($file, 'cards.xlsx', null, null, true)]);
        $report = (string) ob_get_clean();

        return [$client->getResponse()->getContent(), strip_tags(str_replace(['</h4>', '</p>'], [': ', '; '], $report))];
    }

    private function rows(string $file): array
    {
        return IOFactory::load($file)->getActiveSheet()->toArray(null, false, false, false);
    }

    /**
     * Changes the file with PhpSpreadsheet: [row (1 = header) => [column name => value]].
     */
    private function edit(string $file, array $changes): void
    {
        $spreadsheet = IOFactory::load($file);
        $sheet = $spreadsheet->getActiveSheet();
        foreach ($changes as $row => $values) {
            foreach ($values as $column => $value) {
                // PhpSpreadsheet columns start at 1
                $sheet->setCellValueExplicit(
                    [(int) array_search($column, self::HEADER, true) + 1, $row],
                    $value,
                    is_int($value) ? DataType::TYPE_NUMERIC : DataType::TYPE_STRING
                );
            }
        }

        IOFactory::createWriter($spreadsheet, 'Xlsx')->save($file);
    }

    private function fetchCard(string $code)
    {
        return $this->db()->fetchAssoc('SELECT c.name, c.cost, c.text, t.name AS type, s.name AS sphere FROM card c JOIN type t ON t.id = c.type_id JOIN sphere s ON s.id = c.sphere_id WHERE c.code = ?', [$code]);
    }

    /* ----------------------------------------------------------- download */

    public function testDownloadAPack(): void
    {
        $client = $this->createAdminClient();
        $file = $this->download($client, 1);

        $this->assertSame('text/vnd.ms-excel; charset=utf-8', $client->getResponse()->headers->get('Content-Type'));
        $this->assertSame('attachment; filename=coreset.xlsx', $client->getResponse()->headers->get('Content-Disposition'));
        $rows = $this->rows($file);
        // the associations (by name) then the fields of Card, except id and dates; one row per card
        $this->assertSame(self::HEADER, $rows[0]);
        $this->assertCount(1 + count($this->coreCards), $rows);
        $this->assertSame(['Hero', 'Leadership'], array_slice($rows[1], 0, 2));
        $this->assertSame(['01001', 'Aragorn', 'Dúnedain. Noble. Ranger.'], array_slice($rows[1], 3, 3));
        // whole numbers are read back as ints (floats with PHPExcel); booleans are "1" or empty,
        // null values are empty
        $this->assertSame([1, null, 12, 2, 3, 2, 5, null, null, 1, null], array_slice($rows[1], 8));
        $this->assertSame(1, $rows[1][2]);
    }

    public function testDownloadAllCards(): void
    {
        $client = $this->createAdminClient();
        $file = $this->download($client, 0);

        $this->assertSame('attachment; filename=lotrlcgcards.xlsx', $client->getResponse()->headers->get('Content-Disposition'));
        $this->assertCount(1 + (int) $this->db()->fetchColumn('SELECT COUNT(*) FROM card'), $this->rows($file));
    }

    /* ------------------------------------------------------------- upload */

    /**
     * BUG-ish: the texts stored with CRLF line endings come back with LF from the Excel file, so
     * uploading an unchanged download "changes" every card with a line break in its text or
     * flavor (7 cards of the Core Set, 292 in the whole database): their line endings are
     * normalized and their date_update changes. A second upload changes nothing.
     */
    public function testUploadAnUnchangedDownload(): void
    {
        $client = $this->createAdminClient();
        $file = $this->download($client, 1);

        [$response, $report] = $this->upload($client, $file);
        $this->assertSame('7 cards changed or added', $response);
        $this->assertStringContainsString('Legolas: field [text] changed; field [flavor] changed;', $report);
        $this->assertStringNotContainsString("\r", $this->db()->fetchColumn("SELECT text FROM card WHERE code = '01005'"));

        [$response] = $this->upload($client, $file);
        $this->assertSame('0 cards changed or added', $response);
    }

    /**
     * The sample is a download of the Core Set made in production: it is read like a download of
     * the test database (the same 7 cards "changed" by their line endings).
     */
    public function testUploadAProductionExport(): void
    {
        $client = $this->createAdminClient();
        $file = self::temporaryFile('excel').'.xlsx';
        $this->files[] = $file;
        copy(__DIR__.'/../Resources/fixtures/import/core-set.xlsx', $file);
        $this->assertSame(self::HEADER, $this->rows($file)[0]);

        [$response, $report] = $this->upload($client, $file);
        $this->assertSame('7 cards changed or added', $response);
        $this->assertStringContainsString('Legolas: field [text] changed; field [flavor] changed;', $report);

        [$response] = $this->upload($client, $file);
        $this->assertSame('0 cards changed or added', $response);
    }

    public function testUploadChanges(): void
    {
        $client = $this->createAdminClient();
        $file = $this->download($client, 1);
        $this->upload($client, $file);

        // Aragorn (row 2): new name, cost and sphere
        $this->edit($file, [2 => ['name' => 'PHPUnit Aragorn', 'cost' => 4, 'sphere' => 'Tactics']]);
        [$response, $report] = $this->upload($client, $file);

        $this->assertSame('1 cards changed or added', $response);
        $this->assertSame('PHPUnit Aragorn: association [sphere] changed; field [name] changed; field [cost] changed; ', $report);
        $this->assertSame(
            ['name' => 'PHPUnit Aragorn', 'cost' => '4', 'type' => 'Hero', 'sphere' => 'Tactics'],
            array_diff_key($this->fetchCard('01001'), ['text' => 0])
        );
    }

    public function testUnknownCardsAreOnlyCreatedOnRequest(): void
    {
        $client = $this->createAdminClient();
        $file = $this->download($client, 1);
        $this->upload($client, $file);
        $row = count($this->rows($file)) + 1;
        $this->edit($file, [$row => ['type' => 'Ally', 'sphere' => 'Lore', 'position' => 999, 'code' => '99901', 'name' => 'PHPUnit Ally',
            'traits' => 'Test.', 'text' => 'Does nothing.', 'cost' => 2, 'willpower' => 1, 'attack' => 1, 'defense' => 1, 'health' => 2, 'deckLimit' => 3]]);

        [$response] = $this->upload($client, $file);
        $this->assertSame('0 cards changed or added', $response);
        $this->assertFalse($this->fetchCard('99901'));

        [$response] = $this->upload($client, $file, ['create' => '1']);
        $this->assertSame('1 cards changed or added', $response);
        $this->assertSame(['name' => 'PHPUnit Ally', 'cost' => '2', 'text' => 'Does nothing.', 'type' => 'Ally', 'sphere' => 'Lore'], $this->fetchCard('99901'));
    }

    /**
     * An unknown type or sphere name stops the import with a generic exception (500); the
     * changes of the previous rows are not saved (a single flush at the end).
     */
    public function testUnknownAssociationStopsTheImport(): void
    {
        $client = $this->createAdminClient();
        $file = $this->download($client, 1);
        $this->upload($client, $file);
        $this->edit($file, [2 => ['name' => 'PHPUnit Aragorn'], 3 => ['sphere' => 'Nonexistent']]);

        $this->upload($client, $file);

        $this->assertSame(500, $client->getResponse()->getStatusCode());
        $this->assertStringContainsString('cannot find entity [sphere] of name [Nonexistent]', $client->getResponse()->getContent());
        $this->assertSame('Aragorn', $this->fetchCard('01001')['name']);
    }
}
