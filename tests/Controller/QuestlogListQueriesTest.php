<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * The SQL queries of the quest log lists (see docs/issues/questlog-list-optimization.md).
 *
 * The count is exact on the test fixtures: an optimization or a regression makes the test fail,
 * with the queries grouped by SQL in the message; update the expected counts then.
 *
 * DUMP_QUERIES=1 writes every query, with its parameters and origin, to var/log/queries/questlogs-*.txt.
 */
class QuestlogListQueriesTest extends WebTestCase
{
    use DatabaseQueriesTrait;

    /**
     * @return iterable<string, array{string, int}>
     */
    public static function pages(): iterable
    {
        yield 'popular' => ['/questlogs/popular', 8];
        yield 'recent' => ['/questlogs/recent', 8];
        yield 'hottopics' => ['/questlogs/hottopics', 8];
        yield 'halloffame' => ['/questlogs/halloffame', 8];
        yield 'find' => ['/questlogs/find?sort=popularity', 9];
    }

    #[DataProvider('pages')]
    public function testQuestlogListQueries(string $url, int $expected): void
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
