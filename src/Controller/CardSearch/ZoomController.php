<?php

namespace App\Controller\CardSearch;

use App\Repository\CardRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ZoomController extends AbstractController
{
    private CardRepository $cardRepository;
    private string $gameName;
    private string $publisherName;

    public function __construct(
        CardRepository $cardRepository,
        string $gameName,
        string $publisherName
    ) {
        $this->cardRepository = $cardRepository;
        $this->gameName = $gameName;
        $this->publisherName = $publisherName;
    }

    /**
     * @Route("/card/{card_code}", name="cards_zoom")
     */
    public function zoomAction(Request $request, string $card_code): Response
    {
        $card = $this->cardRepository->findOneBy(['code' => $card_code]);
        if (!$card) {
            throw $this->createNotFoundException('Sorry, this card is not in the database (yet?)');
        }
        $game_name = $this->gameName;
        $publisher_name = $this->publisherName;
        $meta = $card->getName().', a '.$card->getSphere()->getName().' '.$card->getType()->getName()." card for {$game_name} from the set ".$card->getPack()->getName()." published by {$publisher_name}.";
        $selectedPackCode = $request->query->get('pack', null);

        return $this->forward(DisplaySearchController::class, ['_route' => $request->attributes->get('_route'), '_route_params' => $request->attributes->get('_route_params'), 'q' => $card->getCode(), 'view' => 'card', 'sort' => 'set', 'pagetitle' => $card->getName(), 'meta' => $meta, 'selected_pack_code' => $selectedPackCode]);
    }
}
