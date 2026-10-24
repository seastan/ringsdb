<?php

declare(strict_types=1);

namespace App\Controller\DeckBuilder;

use App\Controller\CurrentUserTrait;
use App\Services\DeckArchiver;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class OctgnListExportController extends AbstractController
{
    use CurrentUserTrait;

    public function __construct(
        private DeckArchiver $deckArchiver
    ) {
    }

    #[Route(path: '/deck/export/octgn/list', name: 'deck_export_octgn_list', methods: ['GET'])]
    public function __invoke(Request $request): Response
    {
        $list_id = $request->get('ids');

        return $this->deckArchiver->downloadFromSelection($this->currentUser(), $list_id, true);
    }
}
