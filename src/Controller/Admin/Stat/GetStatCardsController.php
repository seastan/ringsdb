<?php

declare(strict_types=1);

namespace App\Controller\Admin\Stat;

use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class GetStatCardsController extends AbstractController
{
    public function __construct(
        private readonly Connection $connection
    ) {
    }

    #[Route(path: '/admin/stat_cards', name: 'app_stat_cards', methods: ['GET'])]
    public function __invoke(Request $request): Response
    {
        // Per-card stats are too heavy to compute on a request worker (they scan a
        // whole month of decklistslot/deckslot and would saturate the shared
        // php-fpm pool, 504-ing all three sites). They are precomputed off-line into
        // stat_cards_cache by `app:stats:precompute-cards` (cron); here we only read.
        $month = $request->query->get('month');
        if (!$month) {
            $month = date('Y-m', strtotime('first day of last month'));
        }

        $step = $request->query->get('step');
        if (!$step) {
            $step = '1';
        }

        $payload = $this->connection->executeQuery('SELECT payload FROM stat_cards_cache WHERE month = ? AND step = ?', [$month, (int) $step])->fetchOne();
        if (false === $payload) {
            return new Response("Per-card stats for {$month} have not been precomputed yet. Run `php bin/console app:stats:precompute-cards {$month}` (scheduled via cron).", 503);
        }

        $response = new Response($payload);
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }
}
