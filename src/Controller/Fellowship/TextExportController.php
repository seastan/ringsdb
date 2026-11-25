<?php

declare(strict_types=1);

namespace App\Controller\Fellowship;

use App\Controller\CurrentUserTrait;
use App\Entity\Fellowship;
use App\Services\FellowshipArchiver;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
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
    public function __invoke(#[MapEntity(id: 'fellowship_id', message: 'This fellowship does not exists.')] Fellowship $fellowship): Response
    {
        return $this->fellowshipArchiver->downloadFromSelection($this->currentUser(), $fellowship, false);
    }
}
