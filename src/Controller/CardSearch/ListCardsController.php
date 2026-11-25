<?php

namespace App\Controller\CardSearch;

use App\Entity\Pack;
use App\Search\SearchKeys;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ListCardsController extends AbstractController
{
    #[Route(path: '/set/{pack_code}/{view}/{sort}/{page}', name: 'cards_list', defaults: ['view' => 'list', 'sort' => 'set', 'page' => 1])]
    public function __invoke(Request $request, #[MapEntity(mapping: ['pack_code' => 'code'], message: 'This pack does not exist')] Pack $pack, string $view, string $sort, int $page): Response
    {
        $key = array_search('pack', SearchKeys::$searchKeys, true);

        return $this->forward(DisplaySearchController::class, [
            '_route' => $request->attributes->get('_route'),
            '_route_params' => $request->attributes->get('_route_params'),
            'q' => $key.':'.$pack->getCode(),
            'view' => $view,
            'sort' => $sort,
            'page' => $page,
            'pagetitle' => $pack->getName(),
        ]);
    }
}
