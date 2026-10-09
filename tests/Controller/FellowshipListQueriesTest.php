<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * The SQL queries of the fellowship lists (see docs/issues/fellowship-list-optimization.md).
 *
 * The count is exact on the test fixtures: an optimization or a regression makes the test fail,
 * with the queries grouped by SQL in the message; update the expected counts then.
 *
 * DUMP_QUERIES=1 writes every query, with its parameters and origin, to var/log/queries/fellowships-*.txt.
 */
class FellowshipListQueriesTest extends WebTestCase
{
    use DatabaseQueriesTrait;

    /**
     * @return iterable<string, array{string, int}>
     */
    public static function pages(): iterable
    {
        yield 'popular' => ['/fellowships/popular', 4];
        yield 'recent' => ['/fellowships/recent', 4];
        yield 'hottopics' => ['/fellowships/hottopics', 4];
        yield 'find' => ['/fellowships/find?sort=popularity', 5];
    }

    #[DataProvider('pages')]
    public function testFellowshipListQueries(string $url, int $expected): void
    {
        $client = static::createClient();
        $client->enableProfiler();
        $client->request('GET', $url);
        $this->assertResponseIsSuccessful();

        $queries = $this->databaseQueries($client);
        if (getenv('DUMP_QUERIES')) {
            $dir = dirname(__DIR__, 2).'/var/log/queries';
            @mkdir($dir, 0777, true);
            $name = (string) preg_replace('/\W+/', '-', trim((string) parse_url($url, PHP_URL_PATH), '/'));
            file_put_contents($dir.'/'.$name.'.txt', self::groupedQueriesReport($queries)."\n\n".self::queriesReport($queries));
        }

        $this->assertCount($expected, $queries, self::groupedQueriesReport($queries));
    }
}
