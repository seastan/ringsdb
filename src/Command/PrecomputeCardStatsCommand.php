<?php

declare(strict_types=1);

namespace App\Command;

use App\Stats\CardStatsCalculator;
use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Precomputes the heavy per-card monthly stats into stat_cards_cache so the
 * admin endpoint (StatController::getStatCardsAction) is a light table read.
 * MUST be run from cron / CLI -- it scans a whole month of decklistslot/deckslot
 * and would saturate the shared php-fpm pool if run on a web worker.
 *
 *   php bin/console app:stats:precompute-cards              # last month
 *   php bin/console app:stats:precompute-cards 2024-03      # a specific month
 *   php bin/console app:stats:precompute-cards --months=3   # last 3 months
 */
class PrecomputeCardStatsCommand extends Command
{
    use StringInputTrait;

    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var CardStatsCalculator
     */
    private $cardStats;

    public function __construct(Connection $connection, CardStatsCalculator $cardStats)
    {
        parent::__construct();
        $this->connection = $connection;
        $this->cardStats = $cardStats;
    }

    protected function configure(): void
    {
        $this
            ->setName('app:stats:precompute-cards')
            ->setDescription('Precompute per-card monthly stats into stat_cards_cache (cron only, never on a web worker)')
            ->addArgument('month', InputArgument::OPTIONAL, 'Month to compute as YYYY-MM (default: last month)')
            ->addOption('months', null, InputOption::VALUE_REQUIRED, 'Number of consecutive months to (re)compute, ending at the given/last month', 1);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        set_time_limit(0);
        ini_set('memory_limit', '1G');

        $calc = $this->cardStats;
        $dbh = $this->connection;

        $month = self::stringArgument($input, 'month') ?: date('Y-m', strtotime('first day of last month'));
        if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
            $output->writeln("<error>month must be YYYY-MM, got '$month'</error>");

            return 1;
        }
        $count = max(1, (int) $input->getOption('months'));

        for ($i = 0; $i < $count; ++$i) {
            $m = date('Y-m', (int) strtotime("$month-01 -$i month"));
            $output->writeln("Computing $m ...");
            foreach ([1, 2, 3] as $step) {
                $t = microtime(true);
                $res = $calc->computeCards($m, (string) $step);
                $payload = (string) json_encode($res);
                $dbh->executeUpdate(
                    'INSERT INTO stat_cards_cache (month, step, payload, computed_at) VALUES (?, ?, ?, NOW())
                     ON DUPLICATE KEY UPDATE payload = VALUES(payload), computed_at = VALUES(computed_at)',
                    [$m, $step, $payload]
                );
                $output->writeln(sprintf('  step %d: %d bytes in %.1fs', $step, strlen($payload), microtime(true) - $t));
            }
        }
        $output->writeln('done');

        return 0;
    }
}
