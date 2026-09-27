<?php

namespace AppBundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Client;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Excel export / import of the cards (ExcelController, admin only): the tests download a pack,
 * change the file with PHPExcel, and upload it back.
 *
 * - the download is a StreamedResponse, sent by Symfony's StreamedResponseListener as soon as
 *   the controller returns it: it is captured with an output buffer around the request;
 * - the upload echoes an HTML report of the changes before returning its response: also
 *   captured.
 *
 * The cards of the Core Set, and any card created, are restored / deleted in tearDown().
 */
class AdminExcelTest extends WebTestCase {
    const HEADER = ['type', 'sphere', 'position', 'code', 'name', 'traits', 'text', 'flavor', 'isUnique', 'cost', 'threat',
        'willpower', 'attack', 'defense', 'health', 'victory', 'quest', 'deckLimit', 'hasErrata'];

    /** @var int */
    private $maxCardId;
    /** @var array */
    private $coreCards;
    /** @var string[] */
    private $files = [];

    protected function setUp(): void {
        $connection = $this->db(static::createClient());
        $this->maxCardId = (int) $connection->fetchColumn('SELECT MAX(id) FROM card');
        $this->coreCards = $connection->fetchAll('SELECT c.* FROM card c JOIN card_printing cp ON cp.card_id = c.id WHERE cp.pack_id = 1');
    }

    protected function tearDown(): void {
        $connection = $this->db(static::createClient());
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

    /**
     * @return mixed
     */
    private function db(Client $client) {
        return $client->getContainer()->get('doctrine')->getConnection();
    }

    /**
     * @return \Symfony\Bundle\FrameworkBundle\Client
     */
    private function createAdminClient() {
        $client = static::createClient();
        $crawler = $client->request('GET', '/login');
        $client->submit($crawler->selectButton('_submit')->form(['_username' => 'admin', '_password' => 'admin']));
        $this->assertTrue($client->getResponse()->isRedirect(), 'Login as admin failed');

        return $client;
    }

    /**
     * Downloads the cards of a pack (0 = all the cards), returns the path of the saved file.
     * @param mixed $packId
     * @return string
     */
    private function download(Client $client, $packId) {
        ob_start();
        $client->request('POST', '/admin/excel/download', ['pack' => $packId]);
        $content = ob_get_clean();

        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $file = tempnam(sys_get_temp_dir(), 'excel') . '.xlsx';
        $this->files[] = $file;
        file_put_contents($file, $content);

        return $file;
    }

    /**
     * @return array [response content, echoed report]
     * @param mixed $file
     */
    private function upload(Client $client, $file, array $parameters = []) {
        ob_start();
        $client->request('POST', '/admin/excel/upload', $parameters, ['upfile' => new UploadedFile($file, 'cards.xlsx', null, filesize($file), null, true)]);
        $report = ob_get_clean();

        return [$client->getResponse()->getContent(), strip_tags(str_replace(['</h4>', '</p>'], [': ', '; '], $report))];
    }

    /**
     * @param mixed $file
     * @return array
     */
    private static function rows($file) {
        return \PHPExcel_IOFactory::load($file)->getActiveSheet()->toArray(null, false, false, false);
    }

    /**
     * Changes the file with PHPExcel: [row (1 = header) => [column name => value]].
     * @param mixed $file
     */
    private static function edit($file, array $changes): void {
        $excel = \PHPExcel_IOFactory::load($file);
        $sheet = $excel->getActiveSheet();
        foreach ($changes as $row => $values) {
            foreach ($values as $column => $value) {
                // PHPExcel documents the column index as a string, it is an int
                /** @phpstan-ignore-next-line */
                $sheet->setCellValueExplicitByColumnAndRow((int) array_search($column, self::HEADER), $row, $value,
                    is_int($value) ? \PHPExcel_Cell_DataType::TYPE_NUMERIC : \PHPExcel_Cell_DataType::TYPE_STRING);
            }
        }
        \PHPExcel_IOFactory::createWriter($excel, 'Excel2007')->save($file);
    }

    /**
     * @param mixed $code
     * @return mixed
     */
    private function fetchCard(Client $client, $code) {
        return $this->db($client)->fetchAssoc('SELECT c.name, c.cost, c.text, t.name AS type, s.name AS sphere FROM card c JOIN type t ON t.id = c.type_id JOIN sphere s ON s.id = c.sphere_id WHERE c.code = ?', [$code]);
    }

    /* ----------------------------------------------------------- download */

    public function testDownloadAPack(): void {
        $client = $this->createAdminClient();
        $file = $this->download($client, 1);

        $this->assertSame('text/vnd.ms-excel; charset=utf-8', $client->getResponse()->headers->get('Content-Type'));
        $this->assertSame('attachment; filename="coreset.xlsx"', $client->getResponse()->headers->get('Content-Disposition'));
        $rows = self::rows($file);
        // the associations (by name) then the fields of Card, except id and dates; one row per card
        $this->assertSame(self::HEADER, $rows[0]);
        $this->assertCount(1 + count($this->coreCards), $rows);
        $this->assertSame(['Hero', 'Leadership'], array_slice($rows[1], 0, 2));
        $this->assertSame(['01001', 'Aragorn', 'Dúnedain. Noble. Ranger.'], array_slice($rows[1], 3, 3));
        // numbers are read back as floats; booleans are "1" or empty, null values are empty
        $this->assertEquals([1, null, 12, 2, 3, 2, 5, null, null, 1, null], array_slice($rows[1], 8));
        $this->assertSame(1.0, $rows[1][2]);
    }

    public function testDownloadAllCards(): void {
        $client = $this->createAdminClient();
        $file = $this->download($client, 0);

        $this->assertSame('attachment; filename="lotrlcgcards.xlsx"', $client->getResponse()->headers->get('Content-Disposition'));
        $this->assertCount(1 + (int) $this->db($client)->fetchColumn('SELECT COUNT(*) FROM card'), self::rows($file));
    }

    /* ------------------------------------------------------------- upload */

    /**
     * BUG-ish: the texts stored with CRLF line endings come back with LF from the Excel file, so
     * uploading an unchanged download "changes" every card with a line break in its text or
     * flavor (7 cards of the Core Set, 292 in the whole database): their line endings are
     * normalized and their date_update changes. A second upload changes nothing.
     */
    public function testUploadAnUnchangedDownload(): void {
        $client = $this->createAdminClient();
        $file = $this->download($client, 1);

        list($response, $report) = $this->upload($client, $file);
        $this->assertSame('7 cards changed or added', $response);
        $this->assertContains('Legolas: field [text] changed; field [flavor] changed;', $report);
        $this->assertNotContains("\r", $this->db($client)->fetchColumn("SELECT text FROM card WHERE code = '01005'"));

        list($response) = $this->upload($client, $file);
        $this->assertSame('0 cards changed or added', $response);
    }

    public function testUploadChanges(): void {
        $client = $this->createAdminClient();
        $file = $this->download($client, 1);
        $this->upload($client, $file);

        // Aragorn (row 2): new name, cost and sphere
        self::edit($file, [2 => ['name' => 'PHPUnit Aragorn', 'cost' => 4, 'sphere' => 'Tactics']]);
        list($response, $report) = $this->upload($client, $file);

        $this->assertSame('1 cards changed or added', $response);
        $this->assertSame('PHPUnit Aragorn: association [sphere] changed; field [name] changed; field [cost] changed; ', $report);
        $this->assertSame(['name' => 'PHPUnit Aragorn', 'cost' => '4', 'type' => 'Hero', 'sphere' => 'Tactics'],
            array_diff_key($this->fetchCard($client, '01001'), ['text' => 0]));
    }

    public function testUnknownCardsAreOnlyCreatedOnRequest(): void {
        $client = $this->createAdminClient();
        $file = $this->download($client, 1);
        $this->upload($client, $file);
        $row = count(self::rows($file)) + 1;
        self::edit($file, [$row => ['type' => 'Ally', 'sphere' => 'Lore', 'position' => 999, 'code' => '99901', 'name' => 'PHPUnit Ally',
            'traits' => 'Test.', 'text' => 'Does nothing.', 'cost' => 2, 'willpower' => 1, 'attack' => 1, 'defense' => 1, 'health' => 2, 'deckLimit' => 3]]);

        list($response) = $this->upload($client, $file);
        $this->assertSame('0 cards changed or added', $response);
        $this->assertFalse($this->fetchCard($client, '99901'));

        list($response) = $this->upload($client, $file, ['create' => '1']);
        $this->assertSame('1 cards changed or added', $response);
        $this->assertSame(['name' => 'PHPUnit Ally', 'cost' => '2', 'text' => 'Does nothing.', 'type' => 'Ally', 'sphere' => 'Lore'], $this->fetchCard($client, '99901'));
    }

    /**
     * An unknown type or sphere name stops the import with a generic exception (500); the
     * changes of the previous rows are not saved (a single flush at the end).
     */
    public function testUnknownAssociationStopsTheImport(): void {
        $client = $this->createAdminClient();
        $file = $this->download($client, 1);
        $this->upload($client, $file);
        self::edit($file, [2 => ['name' => 'PHPUnit Aragorn'], 3 => ['sphere' => 'Nonexistent']]);

        $this->upload($client, $file);

        $this->assertSame(500, $client->getResponse()->getStatusCode());
        $this->assertContains('cannot find entity [sphere] of name [Nonexistent]', $client->getResponse()->getContent());
        $this->assertSame('Aragorn', $this->fetchCard($client, '01001')['name']);
    }
}
