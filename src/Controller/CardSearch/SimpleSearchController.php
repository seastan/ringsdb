<?php

namespace App\Controller\CardSearch;

use App\Search\SearchKeys;
use App\Services\CardsData;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class SimpleSearchController extends AbstractController
{
    public function __construct(private readonly CardsData $cardsData)
    {
    }

    /**
     * Processes the action of the single card search input.
     *
     * @Route("/find", name="cards_find")
     */
    public function findAction(Request $request): RedirectResponse|Response
    {
        $q = (string) $request->query->get('q');
        $q = str_replace('t:campaign', 't:treasure', $q);

        $page = $request->query->get('page') ?: 1;
        $view = $request->query->get('view') ?: 'list';
        $sort = $request->query->get('sort') ?: 'name';
        // we may be able to redirect to a better url if the search is on a single set
        $conditions = $this->cardsData->syntax($q);
        if (1 === count($conditions) && 3 === count($conditions[0]) && ':' == $conditions[0][1]) {
            if ($conditions[0][0] == array_search('pack', SearchKeys::$searchKeys, true)) {
                $url = $this->generateUrl('cards_list', ['pack_code' => $conditions[0][2], 'view' => $view, 'sort' => $sort, 'page' => $page]);

                return $this->redirect($url);
            }

            if ($conditions[0][0] == array_search('cycle', SearchKeys::$searchKeys, true)) {
                $url = $this->generateUrl('cards_cycle', ['cycle_code' => $conditions[0][2], 'view' => $view, 'sort' => $sort, 'page' => $page]);

                return $this->redirect($url);
            }
        }

        return $this->forward(DisplaySearchController::class, [
            'q' => $q,
            'view' => $view,
            'sort' => $sort,
            'page' => $page,
            '_route' => $request->get('_route'),
        ]);
    }
}
