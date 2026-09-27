<?php

namespace AppBundle\Tests\Controller;

use Symfony\Component\DomCrawler\Crawler;

/**
 * Compares pages and downloads to snapshots stored in Tests/Resources/snapshots/pages/.
 *
 * For HTML pages, the snapshot is the visible text of the page (scripts and styles removed), one
 * line per text node. This is strict on the displayed content but not on markup or attributes
 * (CSS classes, CSRF tokens, asset URLs...), which are expected to change during the migration.
 *
 * To (re)generate the snapshots, run the tests with UPDATE_SNAPSHOTS=1, then review the diff:
 *   docker compose exec -e UPDATE_SNAPSHOTS=1 -u www-data symfony php bin/simple-phpunit
 */
trait PageSnapshotTrait {
    private static function pageText(Crawler $crawler) {
        $crawler->filter('script, style, noscript')->each(function (Crawler $node) {
            $domNode = $node->getNode(0);
            $domNode->parentNode->removeChild($domNode);
        });

        $lines = [];
        foreach ($crawler->filterXPath('//body//text()') as $textNode) {
            $line = trim(preg_replace('/\s+/u', ' ', $textNode->nodeValue));
            if ($line !== '') {
                $lines[] = $line;
            }
        }

        return implode("\n", $lines) . "\n";
    }

    private function assertMatchesSnapshot($name, $actual) {
        $file = __DIR__ . '/../Resources/snapshots/pages/' . $name;

        if (getenv('UPDATE_SNAPSHOTS')) {
            if (!is_dir(dirname($file))) {
                mkdir(dirname($file), 0755, true);
            }
            file_put_contents($file, $actual);
        }

        $this->assertFileExists($file, "Missing snapshot $name, run the tests with UPDATE_SNAPSHOTS=1 to create it");
        $this->assertSame(file_get_contents($file), $actual, "Page differs from snapshot $name");
    }
}
