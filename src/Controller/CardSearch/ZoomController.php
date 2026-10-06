<?php

declare(strict_types=1);

namespace App\Controller\CardSearch;

use App\Repository\CardRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ZoomController extends AbstractController
{
    public function __construct(private readonly CardRepository $cardRepository)
    {
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

        $selectedPackCode = $request->query->get('pack');

        return $this->forward(DisplaySearchController::class, [
            '_route' => $request->attributes->get('_route'),
            '_route_params' => $request->attributes->get('_route_params'),
            'q' => $card->getCode(),
            'view' => 'card',
            'sort' => 'set',
            'pagetitle' => $card->getName(),
            'selected_pack_code' => $selectedPackCode,
        ]);
    }
}
