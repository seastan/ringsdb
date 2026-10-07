<?php

declare(strict_types=1);

namespace App\Controller\Fellowship;

use App\Controller\CurrentUserTrait;
use App\Services\FellowshipArchiver;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class TextExportController extends AbstractController
{
    use CurrentUserTrait;

    public function __construct(
        private FellowshipArchiver $fellowshipArchiver
    ) {
    }

    #[Route(path: '/fellowship/export/text/{fellowship_id}', name: 'fellowship_export_text', requirements: ['fellowship_id' => '\d+'], methods: ['GET'])]
    public function __invoke(int $fellowship_id): Response
    {
        return $this->fellowshipArchiver->downloadFromSelection($this->currentUser(), $fellowship_id, false);
    }
}
