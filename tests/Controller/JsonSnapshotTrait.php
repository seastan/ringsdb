<?php

declare(strict_types=1);

namespace App\Tests\Controller;

/**
 * Compares JSON responses to snapshots stored in Tests/Resources/snapshots/api/.
 *
 * Both sides are decoded and re-encoded the same way before comparison, so the check is strict
 * on structure, key order, value types and {} vs [], but ignores whitespace and escaping
 * (e.g. "\/" vs "/", "ú" vs "ú").
 *
 * To (re)generate the snapshots, run the tests with UPDATE_SNAPSHOTS=1, then review the diff:
 *   make phpunit-update-snapshots
 */
trait JsonSnapshotTrait
{
    private static function normalizeJson($json): string
    {
        $data = json_decode($json);
        if (JSON_ERROR_NONE !== json_last_error()) {
            throw new \InvalidArgumentException('Invalid JSON: '.json_last_error_msg());
        }

        return json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION)."\n";
    }

    private function assertMatchesJsonSnapshot($name, $json): void
    {
        $file = __DIR__.'/../Resources/snapshots/api/'.$name.'.json';
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
}
