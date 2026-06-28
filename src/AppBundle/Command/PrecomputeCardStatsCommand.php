<?php

namespace AppBundle\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;

/**
 * Precomputes the heavy per-card monthly stats into stat_cards_cache so the
 * admin endpoint (StatController::getStatCardsAction) is a light table read.
 * MUST be run from cron / CLI -- it scans a whole month of decklistslot/deckslot
 * and would saturate the shared php-fpm pool if run on a web worker.
 *
 *   php app/console app:stats:precompute-cards              # last month
 *   php app/console app:stats:precompute-cards 2024-03      # a specific month
 *   php app/console app:stats:precompute-cards --months=3   # last 3 months
 */
class PrecomputeCardStatsCommand extends ContainerAwareCommand {
    protected function configure() {
        $this
            ->setName('app:stats:precompute-cards')
            ->setDescription('Precompute per-card monthly stats into stat_cards_cache (cron only, never on a web worker)')
            ->addArgument('month', InputArgument::OPTIONAL, 'Month to compute as YYYY-MM (default: last month)')
            ->addOption('months', null, InputOption::VALUE_REQUIRED, 'Number of consecutive months to (re)compute, ending at the given/last month', 1);
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        set_time_limit(0);
        ini_set('memory_limit', '1G');

        $calc = $this->getContainer()->get('app.card_stats');
        $dbh = $this->getContainer()->get('doctrine')->getConnection();

        $month = $input->getArgument('month') ?: date('Y-m', strtotime('first day of last month'));
        if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
            $output->writeln("<error>month must be YYYY-MM, got '$month'</error>");
            return 1;
        }
        $count = max(1, (int) $input->getOption('months'));

        for ($i = 0; $i < $count; $i++) {
            $m = date('Y-m', strtotime("$month-01 -$i month"));
            $output->writeln("Computing $m ...");
            foreach ([1, 2, 3] as $step) {
                $t = microtime(true);
                $res = $calc->computeCards($m, (string) $step);
                $payload = json_encode($res);
                $dbh->executeUpdate(
                    "INSERT INTO stat_cards_cache (month, step, payload, computed_at) VALUES (?, ?, ?, NOW())
                     ON DUPLICATE KEY UPDATE payload = VALUES(payload), computed_at = VALUES(computed_at)",
                    [$m, $step, $payload]
                );
                $output->writeln(sprintf('  step %d: %d bytes in %.1fs', $step, strlen($payload), microtime(true) - $t));
            }
        }
        $output->writeln('done');
        return 0;
    }
}
