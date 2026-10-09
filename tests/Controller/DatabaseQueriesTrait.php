<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use Doctrine\Bundle\DoctrineBundle\DataCollector\DoctrineDataCollector;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\HttpKernel\Profiler\Profile;
use Symfony\Component\VarDumper\Cloner\Data;

/**
 * The SQL queries run during the last request, from the profiler (call $client->enableProfiler()
 * before the request).
 *
 * Each query comes with its origin: the first frame of the backtrace in src/ or in a Twig template
 * (profiling_collect_backtrace, config/packages/test/doctrine.yaml).
 */
trait DatabaseQueriesTrait
{
    /**
     * @return list<array{sql: string, params: mixed, executionMS: float, origin: string}>
     */
    private function databaseQueries(KernelBrowser $client): array
    {
        $profile = $client->getProfile();
        self::assertInstanceOf(Profile::class, $profile, 'the profiler is not enabled');
        /** @var DoctrineDataCollector $collector */
        $collector = $profile->getCollector('db');

        $queries = [];
        foreach ($collector->getQueries()['default'] ?? [] as $query) {
            $params = $query['params'] ?? [];
            $queries[] = [
                'sql' => $query['sql'],
                'params' => $params instanceof Data ? $params->getValue(true) : $params,
                'executionMS' => (float) $query['executionMS'],
                'origin' => self::queryOrigin($query['backtrace'] ?? []),
            ];
        }

        return $queries;
    }

    /**
     * The queries grouped by SQL, with their count and origins, most frequent first.
     *
     * @param list<array{sql: string, params: mixed, executionMS: float, origin: string}> $queries
     */
    private static function groupedQueriesReport(array $queries): string
    {
        $counts = [];
        $origins = [];
        foreach ($queries as $query) {
            $counts[$query['sql']] = ($counts[$query['sql']] ?? 0) + 1;
            $origins[$query['sql']][$query['origin']] = true;
        }
        arsort($counts);

        $report = sprintf("%d queries, %d distinct\n", count($queries), count($counts));
        foreach ($counts as $sql => $count) {
            $report .= sprintf("\n%4d × %s\n       from %s\n", $count, self::shortSql((string) $sql), implode(', ', array_keys($origins[$sql])));
        }

        return $report;
    }

    /**
     * Every query in execution order, with its parameters and origin.
     *
     * @param list<array{sql: string, params: mixed, executionMS: float, origin: string}> $queries
     */
    private static function queriesReport(array $queries): string
    {
        $report = '';
        foreach ($queries as $i => $query) {
            $report .= sprintf(
                "#%d  %.2f ms  %s\n    %s\n    params: %s\n\n",
                $i + 1,
                $query['executionMS'],
                $query['origin'],
                $query['sql'],
                json_encode($query['params'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PARTIAL_OUTPUT_ON_ERROR),
            );
        }

        return $report;
    }

    /**
     * The SQL without its column list: "SELECT … FROM card t0 WHERE t0.id = ?".
     */
    private static function shortSql(string $sql): string
    {
        return (string) preg_replace('/^SELECT (DISTINCT )?(?!COUNT\()\S+ AS .*? FROM /s', 'SELECT $1… FROM ', $sql, 1);
    }

    /**
     * The first frame in src/ and the first one in a compiled Twig template (mapped back to the
     * template and its line), e.g. "src/Controller/IndexController.php:162" or
     * "src/Model/SlotCollectionDecorator.php:180 < Default/index.html.twig:31".
     *
     * @param array<int, array<string, mixed>> $backtrace
     */
    private static function queryOrigin(array $backtrace): string
    {
        $projectDir = dirname(__DIR__, 2).'/';
        $srcOrigin = null;
        foreach ($backtrace as $frame) {
            $file = $frame['file'] ?? null;
            $line = $frame['line'] ?? null;
            if (!is_string($file) || !is_int($line)) {
                continue;
            }
            if (str_starts_with($file, $projectDir.'var/cache/') && str_contains($file, '/twig/')) {
                $twigOrigin = self::twigOrigin($file, $line);

                return null === $srcOrigin ? $twigOrigin : $srcOrigin.' < '.$twigOrigin;
            }
            if (str_starts_with($file, $projectDir.'src/')) {
                $srcOrigin ??= substr($file, strlen($projectDir)).':'.$line;
            }
        }

        return $srcOrigin ?? '?';
    }

    /**
     * Compiled templates hold their name in a "/* name *\/" comment and "// line N" markers.
     */
    private static function twigOrigin(string $file, int $line): string
    {
        $lines = file($file) ?: [];
        $template = basename($file);
        foreach ($lines as $source) {
            if (1 === preg_match('#^/\* (\S+\.twig) \*/$#', rtrim($source), $match)) {
                $template = $match[1];
                break;
            }
        }
        $templateLine = '?';
        for ($i = min($line, count($lines)) - 1; $i >= 0; --$i) {
            if (1 === preg_match('#^\s*// line (\d+)$#', rtrim($lines[$i]), $match)) {
                $templateLine = $match[1];
                break;
            }
        }

        return $template.':'.$templateLine;
    }
}
