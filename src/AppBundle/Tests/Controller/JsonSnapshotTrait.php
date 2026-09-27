<?php

namespace AppBundle\Tests\Controller;

/**
 * Compares JSON responses to snapshots stored in Tests/Resources/snapshots/api/.
 *
 * Both sides are decoded and re-encoded the same way before comparison, so the check is strict
 * on structure, key order, value types and {} vs [], but ignores whitespace and escaping
 * (e.g. "\/" vs "/", "ú" vs "ú").
 *
 * To (re)generate the snapshots, run the tests with UPDATE_SNAPSHOTS=1, then review the diff:
 *   docker compose exec -e UPDATE_SNAPSHOTS=1 -u www-data symfony php bin/simple-phpunit
 */
trait JsonSnapshotTrait {
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
}
