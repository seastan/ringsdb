<?php

declare(strict_types=1);

namespace App\Controller\Questlog;

use App\Controller\CurrentUserTrait;
use App\Services\QuestlogArchiver;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class TextExportController extends AbstractController
{
    use CurrentUserTrait;

    private QuestlogArchiver $questlogArchiver;

    public function __construct(
        QuestlogArchiver $questlogArchiver
    ) {
        $this->questlogArchiver = $questlogArchiver;
    }

    /**
     * @Route(
     *     "/questlog/export/text/{questlog_id}",
     *     name="questlog_export_text",
     *     methods={"GET"},
     *     requirements={"questlog_id"="\d+"}
     * )
     */
    public function textexportAction($questlog_id): Response
    {
        return $this->questlogArchiver->downloadFromSelection($this->currentUser(), $questlog_id, false);
    }
}
