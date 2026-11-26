<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * The SQL queries of the card search form.
 *
 * The count is exact on the test fixtures: an optimization or a regression makes the test fail,
 * with the queries grouped by SQL in the message; update the expected count then.
 *
 * DUMP_QUERIES=1 writes every query, with its parameters and origin, to var/log/queries/search.txt.
 */
class CardSearchQueriesTest extends WebTestCase
{
    use DatabaseQueriesTrait;

    public function testSearchFormQueries(): void
    {
        $client = static::createClient();
        $client->enableProfiler();
        $client->request('GET', '/search');
        $this->assertResponseIsSuccessful();

        $queries = $this->databaseQueries($client);
        if (getenv('DUMP_QUERIES')) {
            $dir = dirname(__DIR__, 2).'/var/log/queries';
            @mkdir($dir, 0777, true);
            file_put_contents($dir.'/search.txt', self::groupedQueriesReport($queries)."\n\n".self::queriesReport($queries));
        }

        $this->assertCount(7, $queries, self::groupedQueriesReport($queries));
    }
}
