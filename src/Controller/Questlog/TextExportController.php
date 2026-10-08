<?php

declare(strict_types=1);

namespace App\Controller\Questlog;

use App\Controller\CurrentUserTrait;
use App\Entity\Questlog;
use App\Services\QuestlogArchiver;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class TextExportController extends AbstractController
{
    use CurrentUserTrait;

    public function __construct(
        private QuestlogArchiver $questlogArchiver
    ) {
    }

    #[Route(path: '/questlog/export/text/{questlog_id}', name: 'questlog_export_text', requirements: ['questlog_id' => '\d+'], methods: ['GET'])]
    public function __invoke(#[MapEntity(id: 'questlog_id', message: 'This questlog does not exists.')] Questlog $questlog): Response
    {
        return $this->questlogArchiver->downloadFromSelection($this->currentUser(), $questlog, false);
    }
}
