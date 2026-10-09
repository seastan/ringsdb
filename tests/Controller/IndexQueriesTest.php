<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * The SQL queries of the home page (see docs/issues/index-optimization.md).
 *
 * The count is exact on the test fixtures: an optimization or a regression makes the test fail,
 * with the queries grouped by SQL in the message; update EXPECTED_QUERIES then.
 *
 * DUMP_QUERIES=1 writes every query, with its parameters and origin, to var/log/queries/index.txt.
 */
class IndexQueriesTest extends WebTestCase
{
    use DatabaseQueriesTrait;

    private const int EXPECTED_QUERIES = 475;

    public function testHomePageQueries(): void
    {
        $client = static::createClient();
        $client->enableProfiler();
        $client->request('GET', '/');
        $this->assertResponseIsSuccessful();

        $queries = $this->databaseQueries($client);
        if (getenv('DUMP_QUERIES')) {
            $dir = dirname(__DIR__, 2).'/var/log/queries';
            @mkdir($dir, 0777, true);
            file_put_contents($dir.'/index.txt', self::groupedQueriesReport($queries)."\n\n".self::queriesReport($queries));
        }

        $this->assertCount(self::EXPECTED_QUERIES, $queries, self::groupedQueriesReport($queries));
    }
}
