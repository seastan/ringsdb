<?php

namespace AppBundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Client;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * CSV import of a pack (CSVController, POST /admin/csv/upload, admin only): fields "code",
 * "old_code" (to rename a pack) and "name" of the pack, and the CSV file "upfile", as produced by
 * BeornJSONtoRingsDBcsv.py from a Hall of Beorn export: a header line, then one card per line.
 * The lines end with CRLF, the line breaks in the texts are LF.
 *
 * The sample is the ALeP pack "The Hobbit" (21 cards), already in the database. Everything the
 * tests create is deleted, and the cards, printings and pack they change are restored, in
 * tearDown().
 */
class AdminCsvTest extends WebTestCase {
    use \AppBundle\Tests\TemporaryFileTrait;

    const SAMPLE = __DIR__ . '/../Resources/fixtures/import/alep-the-hobbit.csv';

    /** @var int[] */
    private $maxIds = [];
    /** @var array[] rows to restore, by table */
    private $backup = [];
    /** @var string[] */
    private $files = [];

    protected function setUp(): void {
        $connection = $this->db(static::createClient());
        foreach (['card', 'card_printing', 'pack'] as $table) {
            $this->maxIds[$table] = (int) $connection->fetchColumn("SELECT MAX(id) FROM $table");
        }
        $packs = "SELECT id FROM pack WHERE code IN ('THo', 'ALePMotKA')";
        $this->backup = [
            'pack' => $connection->fetchAll("SELECT * FROM pack WHERE id IN ($packs)"),
            'card_printing' => $connection->fetchAll("SELECT * FROM card_printing WHERE pack_id IN ($packs)"),
            'card' => $connection->fetchAll("SELECT * FROM card WHERE id IN (SELECT card_id FROM card_printing WHERE pack_id IN ($packs))"),
        ];
    }

    protected function tearDown(): void {
        $connection = $this->db(static::createClient());
        foreach (['card_printing', 'card', 'pack'] as $table) {
            $connection->exec("DELETE FROM $table WHERE id > {$this->maxIds[$table]}");
        }
        foreach ($this->backup as $table => $rows) {
            foreach ($rows as $row) {
                $connection->update($table, $row, ['id' => $row['id']]);
            }
        }
        foreach ($this->files as $file) {
            @unlink($file);
        }
        parent::tearDown();
    }

    /* ------------------------------------------------------------ helpers */

    /**
     * @return \Doctrine\DBAL\Connection
     */
    private function db(Client $client) {
        return $client->getContainer()->get('doctrine')->getConnection();
    }

    private function createAdminClient(): Client {
        $client = static::createClient();
        $crawler = $client->request('GET', '/login');
        $client->submit($crawler->selectButton('_submit')->form(['_username' => 'admin', '_password' => 'admin']));
        $this->assertTrue($client->getResponse()->isRedirect(), 'Login as admin failed');

        return $client;
    }

    /**
     * @return string the response
     */
    private function upload(Client $client, string $file, string $code, string $name, string $oldCode = ''): string {
        // the upload may be moved: send a copy
        $copy = self::temporaryFile('csv');
        $this->files[] = $copy;
        copy($file, $copy);
        $client->request('POST', '/admin/csv/upload', ['code' => $code, 'old_code' => $oldCode, 'name' => $name],
            ['upfile' => new UploadedFile($copy, 'pack.csv', 'text/csv', (int) filesize($copy), null, true)]);
        $this->assertSame(200, $client->getResponse()->getStatusCode());

        return (string) $client->getResponse()->getContent();
    }

    /**
     * The rows of the sample (header first), changed by $change (row => row, or null to drop it),
     * written to a temporary file in the same format: lines ending with CRLF, LF in the texts.
     */
    private function sampleVariant(callable $change): string {
        $in = fopen(self::SAMPLE, 'r');
        $this->assertNotFalse($in);
        fseek($in, 3); // UTF-8 BOM
        $header = (array) fgetcsv($in);
        $lines = [];
        while (is_array($row = fgetcsv($in))) {
            $row = $change(array_combine($header, $row));
            if ($row !== null) {
                $lines[] = self::csvLine(array_values($row));
            }
        }
        fclose($in);
        $file = self::temporaryFile('csv');
        $this->files[] = $file;
        file_put_contents($file, "\xEF\xBB\xBF" . self::csvLine($header) . implode('', $lines));

        return $file;
    }

    private static function csvLine(array $values): string {
        $stream = fopen('php://memory', 'r+');
        self::assertNotFalse($stream);
        fputcsv($stream, $values);
        rewind($stream);

        return rtrim((string) stream_get_contents($stream), "\n") . "\r\n";
    }

    /**
     * @return array the card and its printing in the pack, by the card name
     */
    private function fetchPrinting(Client $client, string $packCode, string $name): array {
        $row = $this->db($client)->fetchAssoc('SELECT c.id, c.code, c.name, c.position, c.health, c.text, t.name AS type, s.name AS sphere,
                cp.quantity, cp.illustrator, cp.octgnid
            FROM card_printing cp JOIN pack p ON p.id = cp.pack_id JOIN card c ON c.id = cp.card_id
            JOIN type t ON t.id = c.type_id JOIN sphere s ON s.id = c.sphere_id
            WHERE p.code = ? AND c.name LIKE ?', [$packCode, "%$name"]);
        $this->assertNotFalse($row, "$name in $packCode");

        return $row;
    }

    private function rowCount(Client $client, string $table): int {
        return (int) $this->db($client)->fetchColumn("SELECT COUNT(*) FROM $table");
    }

    /* -------------------------------------------------------------- tests */

    /**
     * Uploading the pack again creates nothing: its printings are found by octgnid.
     *
     * BUG: the card fields of the CSV are written to the canonical card, even for a reprint. Beorn
     * is a reprint in this pack (card 131005, from another pack): the upload gives it the code,
     * position, text and flavor of the ALeP printing (503991).
     */
    public function testUploadAnUnchangedPack(): void {
        $client = $this->createAdminClient();
        $counts = [$this->rowCount($client, 'card'), $this->rowCount($client, 'card_printing'), $this->rowCount($client, 'pack')];
        $bilbo = $this->fetchPrinting($client, 'THo', 'Bilbo Baggins');
        $beorn = $this->fetchPrinting($client, 'THo', 'Beorn');
        $this->assertSame(['131005', '5'], [$beorn['code'], $beorn['position']]);

        $this->assertSame('Done', $this->upload($client, self::SAMPLE, 'THo', 'ALeP - The Hobbit'));

        $this->assertSame($counts, [$this->rowCount($client, 'card'), $this->rowCount($client, 'card_printing'), $this->rowCount($client, 'pack')]);
        $this->assertSame($bilbo, $this->fetchPrinting($client, 'THo', 'Bilbo Baggins'));
        $beornAfter = $this->fetchPrinting($client, 'THo', 'Beorn');
        $this->assertSame([$beorn['id'], '503991', '991'], [$beornAfter['id'], $beornAfter['code'], $beornAfter['position']]);
    }

    /**
     * A new pack (unknown code and old code) is created in the "ALeP" cycle, or the last cycle as
     * there is no such cycle, with a release date in 2030; the cards unknown by octgnid and by
     * code are created, with one printing each.
     */
    public function testUploadANewPack(): void {
        $client = $this->createAdminClient();
        $counts = [$this->rowCount($client, 'card'), $this->rowCount($client, 'card_printing'), $this->rowCount($client, 'pack')];
        $file = $this->sampleVariant(function (array $row) {
            $row['pack'] = 'PHPUnit Pack';
            $row['code'] = '99' . substr($row['code'], 2);
            $row['octgnid'] = 'phpunit-' . $row['octgnid'];

            return $row;
        });

        $this->assertSame('Done', $this->upload($client, $file, 'PHPU', 'PHPUnit Pack'));

        $this->assertSame([$counts[0] + 21, $counts[1] + 21, $counts[2] + 1],
            [$this->rowCount($client, 'card'), $this->rowCount($client, 'card_printing'), $this->rowCount($client, 'pack')]);
        $pack = $this->db($client)->fetchAssoc('SELECT p.name, p.position, p.size, p.date_release, y.code AS cycle FROM pack p JOIN cycle y ON y.id = p.cycle_id WHERE p.code = ?', ['PHPU']);
        $lastCycle = $this->db($client)->fetchColumn('SELECT code FROM cycle ORDER BY id DESC LIMIT 1');
        $this->assertSame(['name' => 'PHPUnit Pack', 'position' => '1', 'size' => '1', 'date_release' => '2030-02-01', 'cycle' => $lastCycle], $pack);

        $beorn = $this->fetchPrinting($client, 'PHPU', 'Beorn');
        $this->assertSame(['993991', 'Hero', 'Tactics', '10', '1', 'Steven Shan', 'phpunit-2570109c-b9ed-4af5-9f26-4cb8712605c9'],
            [$beorn['code'], $beorn['type'], $beorn['sphere'], $beorn['health'], $beorn['quantity'], $beorn['illustrator'], $beorn['octgnid']]);
        // the line break of the text is kept, the lines of the file are not mixed up
        $this->assertSame("Sentinel. Cannot have attachments. Immune to player card effects.\nBeorn does not exhaust to defend.", $beorn['text']);
    }

    /**
     * The cards of the pack that are not in the CSV any more are not deleted: their name is
     * prefixed with "[deleted]" and their code gets a unique suffix.
     */
    public function testCardsMissingFromTheCsvAreMarkedDeleted(): void {
        $client = $this->createAdminClient();
        $file = $this->sampleVariant(function (array $row) {
            return $row['name'] === 'Bilbo Baggins' ? null : $row;
        });

        $this->assertSame('Done', $this->upload($client, $file, 'THo', 'ALeP - The Hobbit'));

        $bilbo = $this->fetchPrinting($client, 'THo', 'Bilbo Baggins');
        $this->assertSame('[deleted] Bilbo Baggins', $bilbo['name']);
        $this->assertRegExp('/^503007_\w+$/', $bilbo['code']);
        $this->assertSame('Lucky Number', $this->fetchPrinting($client, 'THo', 'Lucky Number')['name']);
    }

    /**
     * The old code finds the pack when the code is new: the pack is renamed, its cards are kept.
     */
    public function testRenameAPackWithItsOldCode(): void {
        $client = $this->createAdminClient();
        $packs = $this->rowCount($client, 'pack');

        $this->assertSame('Done', $this->upload($client, self::SAMPLE, 'THo2', 'ALeP - The Hobbit (renamed)', 'THo'));

        $this->assertSame($packs, $this->rowCount($client, 'pack'));
        $this->assertSame('ALeP - The Hobbit (renamed)', $this->db($client)->fetchColumn("SELECT name FROM pack WHERE code = 'THo2'"));
        $this->assertSame('Bilbo Baggins', $this->fetchPrinting($client, 'THo2', 'Bilbo Baggins')['name']);
    }

    /**
     * Only the lines ending with CRLF are rows: with LF line endings, the whole file is one row.
     */
    public function testCsvWithLfLineEndings(): void {
        $client = $this->createAdminClient();
        $file = self::temporaryFile('csv');
        $this->files[] = $file;
        file_put_contents($file, str_replace("\r\n", "\n", (string) file_get_contents(self::SAMPLE)));

        $this->assertSame('No cards found in the CSV file', $this->upload($client, $file, 'THo', 'ALeP - The Hobbit'));
    }

    public function testCsvWithoutCards(): void {
        $client = $this->createAdminClient();
        $file = $this->sampleVariant(function () {
            return null;
        });

        $this->assertSame('No cards found in the CSV file', $this->upload($client, $file, 'THo', 'ALeP - The Hobbit'));
    }
}
