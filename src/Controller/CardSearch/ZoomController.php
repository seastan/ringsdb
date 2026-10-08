<?php

declare(strict_types=1);

namespace App\Controller\CardSearch;

use App\Entity\Card;
use App\Model\CardZoomDto;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Routing\Attribute\Route;

class ZoomController extends AbstractController
{
    #[Route(path: '/card/{card_code}', name: 'cards_zoom')]
    public function __invoke(Request $request, #[MapEntity(mapping: ['card_code' => 'code'], message: 'Sorry, this card is not in the database (yet?)')] Card $card, #[MapQueryString] CardZoomDto $query = new CardZoomDto()): Response
    {
        $selectedPackCode = $query->pack;

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
