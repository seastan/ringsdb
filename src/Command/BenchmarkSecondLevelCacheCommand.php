<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\Card;
use Doctrine\ORM\Cache\Logging\CacheLoggerChain;
use Doctrine\ORM\Cache\Logging\StatisticsCacheLogger;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Loads every card through the ORM twice: once with an empty second-level cache (the cards come
 * from the database and are put in the cache), then once more after clearing the entity manager
 * (the cards come from the cache), and compares the timings.
 */
class BenchmarkSecondLevelCacheCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('app:benchmark:second-level-cache')
             ->setDescription('Compare loading the cards from the database and from the Doctrine second-level cache');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $cache = $this->entityManager->getCache();
        $cacheConfiguration = $this->entityManager->getConfiguration()->getSecondLevelCacheConfiguration();
        if ($cache === null || $cacheConfiguration === null) {
            $io->error('The Doctrine second-level cache is disabled.');

            return Command::FAILURE;
        }

        // our own statistics, next to the logger the bundle may already have set (debug)
        $statistics = new StatisticsCacheLogger();
        $loggerChain = new CacheLoggerChain();
        if ($cacheConfiguration->getCacheLogger() !== null) {
            $loggerChain->setLogger('previous', $cacheConfiguration->getCacheLogger());
        }
        $loggerChain->setLogger('benchmark', $statistics);
        $cacheConfiguration->setCacheLogger($loggerChain);

        /** @var list<int> $ids */
        $ids = array_map('intval', $this->entityManager->getConnection()->fetchFirstColumn('SELECT id FROM card ORDER BY id'));
        $io->writeln(count($ids).' cards');

        $cache->evictEntityRegion(Card::class);
        $this->entityManager->clear();
        [$missTime, $missStats] = $this->loadCards($ids, $statistics);

        // without clear(), find() would return the entities of the identity map
        $this->entityManager->clear();
        [$hitTime, $hitStats] = $this->loadCards($ids, $statistics);

        $io->table(
            ['Pass', 'Total (ms)', 'Per card (ms)', 'Cache hits', 'Cache misses', 'Cache puts'],
            [
                ['1. cache miss', ...$this->formatRow($missTime, count($ids)), ...$missStats],
                ['2. cache hit', ...$this->formatRow($hitTime, count($ids)), ...$hitStats],
            ]
        );
        if ($hitTime > 0) {
            $io->writeln(sprintf('The cache hit pass is %.1f times as fast.', $missTime / $hitTime));
        }

        return Command::SUCCESS;
    }

    /**
     * @param list<int> $ids
     *
     * @return array{float, array{int, int, int}} the time in milliseconds and the hit, miss and
     *                                            put counts of the cache
     */
    private function loadCards(array $ids, StatisticsCacheLogger $statistics): array
    {
        $statistics->clearStats();

        $start = hrtime(true);
        foreach ($ids as $id) {
            $this->entityManager->find(Card::class, $id);
        }
        $time = (hrtime(true) - $start) / 1e6;

        return [$time, [$statistics->getHitCount(), $statistics->getMissCount(), $statistics->getPutCount()]];
    }

    /**
     * @return array{string, string}
     */
    private function formatRow(float $time, int $count): array
    {
        return [number_format($time, 1), $count > 0 ? number_format($time / $count, 3) : '-'];
    }
}
