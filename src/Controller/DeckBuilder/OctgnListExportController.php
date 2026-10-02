<?php

declare(strict_types=1);

namespace App\Controller\DeckBuilder;

use App\Controller\CurrentUserTrait;
use App\Services\DeckArchiver;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class OctgnListExportController extends AbstractController
{
    use CurrentUserTrait;
    private DeckArchiver $deckArchiver;

    public function __construct(
        DeckArchiver $deckArchiver
    ) {
        $this->deckArchiver = $deckArchiver;
    }

    /**
     * @Route("/deck/export/octgn/list", name="deck_export_octgn_list", methods={"GET"})
     */
    public function octgnexportListAction(Request $request): Response
    {
        $list_id = $request->get('ids');

        return $this->deckArchiver->downloadFromSelection($this->currentUser(), $list_id, true);
    }
}
