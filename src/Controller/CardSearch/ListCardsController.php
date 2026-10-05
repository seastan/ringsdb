<?php

namespace App\Controller\CardSearch;

use App\Repository\PackRepository;
use App\Search\SearchKeys;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ListCardsController extends AbstractController
{
    private PackRepository $packRepository;

    public function __construct(
        PackRepository $packRepository
    ) {
        $this->packRepository = $packRepository;
    }

    /**
     * @Route(
     *     "/set/{pack_code}/{view}/{sort}/{page}",
     *     name="cards_list",
     *     defaults={"view"="list", "sort"="set", "page"=1}
     * )
     */
    public function listAction(Request $request, string $pack_code, string $view, string $sort, int $page): Response
    {
        $pack = $this->packRepository->findOneBy(['code' => $pack_code]);
        if (!$pack) {
            throw $this->createNotFoundException('This pack does not exist');
        }

        $key = array_search('pack', SearchKeys::$searchKeys, true);

        return $this->forward(DisplaySearchController::class, [
            '_route' => $request->attributes->get('_route'),
            '_route_params' => $request->attributes->get('_route_params'),
            'q' => $key.':'.$pack_code,
            'view' => $view,
            'sort' => $sort,
            'page' => $page,
            'pagetitle' => $pack->getName(),
        ]);
    }
}
