<?php

namespace App\Controller\CardSearch;

use App\Repository\CycleRepository;
use App\Search\SearchKeys;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class GetCycleController extends AbstractController
{
    private CycleRepository $cycleRepository;
    private string $gameName;
    private string $publisherName;

    public function __construct(
        CycleRepository $cycleRepository,
        string $gameName,
        string $publisherName
    ) {
        $this->cycleRepository = $cycleRepository;
        $this->gameName = $gameName;
        $this->publisherName = $publisherName;
    }

    /**
     * @Route(
     *     "/cycle/{cycle_code}/{view}/{sort}/{page}",
     *     name="cards_cycle",
     *     defaults={"view"="list", "sort"="sphere", "page"=1}
     * )
     */
    public function cycleAction(Request $request, string $cycle_code, string $view, string $sort, int $page): Response
    {
        $cycle = $this->cycleRepository->findOneBy(['code' => $cycle_code]);
        if (!$cycle) {
            throw $this->createNotFoundException('This cycle does not exist');
        }
        $game_name = $this->gameName;
        $publisher_name = $this->publisherName;
        $meta = $cycle->getName().", a cycle of adventure packs for {$game_name} published by {$publisher_name}.";
        $key = array_search('cycle', SearchKeys::$searchKeys);

        return $this->forward(DisplaySearchController::class, ['_route' => $request->attributes->get('_route'), '_route_params' => $request->attributes->get('_route_params'), 'q' => $key.':'.$cycle_code, 'view' => $view, 'sort' => $sort, 'page' => $page, 'pagetitle' => $cycle->getName(), 'meta' => $meta]);
    }
}
