<?php

declare(strict_types=1);

namespace App\Controller\Admin\Stat;

use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class GetStatPacksController extends AbstractController
{
    use StatPacksTrait;

    public function __construct(
        private readonly Connection $connection
    ) {
    }

    #[Route(path: '/admin/stat_packs', name: 'app_stat_packs', methods: ['GET'])]
    public function __invoke(Request $request): Response
    {
        /* @var $this->connection Connection */
        $packs = $this->getPacks();
        $pack_rules = $this->getPackRuless();
        $quests = $this->getQuests();
        $res = ['packs' => $packs, 'pack_rules' => $pack_rules, 'quests' => $quests];

        return new JsonResponse($res);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getQuests(): array
    {
        $query = "SELECT p.name, GROUP_CONCAT(s.name SEPARATOR ';') AS quests\nFROM scenario s\nJOIN pack p\nON s.pack_id = p.id\nGROUP BY p.name\nORDER BY p.name";
        $quests = $this->connection->executeQuery($query, [])->fetchAllAssociative();
        for ($i = 0; $i < count($quests); ++$i) {
            $quests[$i]['quests'] = explode(';', $quests[$i]['quests']);
        }

        return $quests;
    }
}
