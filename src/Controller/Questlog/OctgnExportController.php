<?php

declare(strict_types=1);

namespace App\Controller\Questlog;

use App\Controller\CurrentUserTrait;
use App\Services\QuestlogArchiver;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class OctgnExportController extends AbstractController
{
    use CurrentUserTrait;

    public function __construct(
        private QuestlogArchiver $questlogArchiver
    ) {
    }

    #[Route(path: '/questlog/export/octgn/{questlog_id}', name: 'questlog_export_octgn', requirements: ['questlog_id' => '\d+'], methods: ['GET'])]
    public function __invoke(int $questlog_id): Response
    {
        return $this->questlogArchiver->downloadFromSelection($this->currentUser(), $questlog_id, true);
    }
}
