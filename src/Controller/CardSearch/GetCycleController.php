<?php

namespace App\Controller\CardSearch;

use App\Entity\Cycle;
use App\Search\SearchKeys;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class GetCycleController extends AbstractController
{
    #[Route(path: '/cycle/{cycle_code}/{view}/{sort}/{page}', name: 'cards_cycle', defaults: ['view' => 'list', 'sort' => 'sphere', 'page' => 1])]
    public function __invoke(Request $request, #[MapEntity(mapping: ['cycle_code' => 'code'], message: 'This cycle does not exist')] Cycle $cycle, string $view, string $sort, int $page): Response
    {
        $key = array_search('cycle', SearchKeys::$searchKeys, true);

        return $this->forward(DisplaySearchController::class, [
            '_route' => $request->attributes->get('_route'),
            '_route_params' => $request->attributes->get('_route_params'),
            'q' => $key.':'.$cycle->getCode(),
            'view' => $view,
            'sort' => $sort,
            'page' => $page,
            'pagetitle' => $cycle->getName(),
        ]);
    }
}
