<?php

declare(strict_types=1);

namespace App\Controller\CardSearch;

use App\Entity\Cycle;
use App\Entity\Pack;
use App\Repository\CardPrintingRepository;
use App\Repository\CardRepository;
use App\Repository\CycleRepository;
use App\Repository\PackRepository;
use App\Repository\SphereRepository;
use App\Repository\TypeRepository;
use App\Services\CardsData;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class SearchController extends AbstractController
{
    /**
     * @var CardsData
     */
    private $cardsData;
    /**
     * @var int
     */
    private $cacheExpiration;
    /**
     * @var string|null
     */
    private $gameName;
    /**
     * @var string|null
     */
    private $publisherName;
    /**
     * @var CardPrintingRepository
     */
    private $cardPrintingRepository;
    /**
     * @var CycleRepository
     */
    private $cycleRepository;
    /**
     * @var PackRepository
     */
    private $packRepository;
    /**
     * @var SphereRepository
     */
    private $sphereRepository;

    public function __construct(CardsData $cardsData, int $cacheExpiration, ?string $gameName, ?string $publisherName, CardPrintingRepository $cardPrintingRepository, CycleRepository $cycleRepository, PackRepository $packRepository, SphereRepository $sphereRepository)
    {
        $this->cardsData = $cardsData;
        $this->cacheExpiration = $cacheExpiration;
        $this->gameName = $gameName;
        $this->publisherName = $publisherName;
        $this->cardPrintingRepository = $cardPrintingRepository;
        $this->cycleRepository = $cycleRepository;
        $this->packRepository = $packRepository;
        $this->sphereRepository = $sphereRepository;
    }
    /**
     * @var array<string, string>
     */
    public static $searchKeys = ['' => 'code', 'a' => 'attack', 'b' => 'threat', 'c' => 'cycle', 'd' => 'defense', 'e' => 'pack', 'f' => 'flavor', 'h' => 'health', 'i' => 'illustrator', 'k' => 'traits', 'o' => 'cost', 's' => 'sphere', 't' => 'type', 'u' => 'isUnique', 'w' => 'willpower', 'x' => 'text', 'y' => 'quantity', 'z' => 'hasErrata'];
    /**
     * @var array<string, string>
     */
    public static $searchTypes = ['' => 'string', 'f' => 'string', 'i' => 'string', 'k' => 'string', 'x' => 'string', 'e' => 'code', 's' => 'code', 't' => 'code', 'c' => 'code', 'a' => 'integer', 'b' => 'integer', 'd' => 'integer', 'h' => 'integer', 'o' => 'integer', 'w' => 'integer', 'y' => 'integer', 'u' => 'boolean', 'z' => 'boolean'];

    /**
     * @Route("/search", name="cards_search")
     */
    public function formAction(TypeRepository $typeRepository): Response
    {
        $response = new Response();
        $response->setPublic();
        $response->setMaxAge($this->cacheExpiration);
        $dbh = $this->getDoctrine()->getConnection();
        $list_packs = $this->packRepository->findBy([], ['dateRelease' => 'ASC', 'position' => 'ASC']);
        $packs = [];
        foreach ($list_packs as $pack) {
            /* @var $pack Pack */
            $packs[] = ['name' => $pack->getName(), 'code' => $pack->getCode()];
        }
        $list_cycles = $this->cycleRepository->findBy([], ['position' => 'ASC']);
        $cycles = [];
        foreach ($list_cycles as $cycle) {
            /* @var $cycle Cycle */
            $cycles[] = ['name' => $cycle->getName(), 'code' => $cycle->getCode()];
        }
        $types = $typeRepository->findBy([], ['name' => 'ASC']);
        $spheres = $this->sphereRepository->findBy([], ['id' => 'ASC']);
        $traits = $this->cardsData->getDistinctTraits();
        $traits = array_filter(array_keys($traits));
        sort($traits);
        $list_illustrators = $dbh->executeQuery("SELECT DISTINCT illustrator FROM card_printing WHERE illustrator IS NOT NULL AND illustrator != '' ORDER BY illustrator")->fetchAll();
        $illustrators = array_map(fn ($card) => $card['illustrator'], $list_illustrators);

        return $this->render('Search/searchform.html.twig', ['pagetitle' => 'Card Search', 'pagedescription' => 'Find all the cards of the game, easily searchable.', 'packs' => $packs, 'cycles' => $cycles, 'types' => $types, 'spheres' => $spheres, 'traits' => $traits, 'illustrators' => $illustrators, 'allsets' => $this->renderView('Default/allsets.html.twig', ['data' => $this->cardsData->allSetsData()])], $response);
    }

    /**
     * @Route("/card/{card_code}", name="cards_zoom")
     */
    public function zoomAction($card_code, Request $request, CardRepository $cardRepository): Response
    {
        $card = $cardRepository->findOneBy(['code' => $card_code]);
        if (!$card) {
            throw $this->createNotFoundException('Sorry, this card is not in the database (yet?)');
        }
        $game_name = $this->gameName;
        $publisher_name = $this->publisherName;
        $meta = $card->getName().', a '.$card->getSphere()->getName().' '.$card->getType()->getName()." card for {$game_name} from the set ".$card->getPack()->getName()." published by {$publisher_name}.";
        $selectedPackCode = $request->query->get('pack', null);

        return $this->forward(DisplaySearchController::class, ['_route' => $request->attributes->get('_route'), '_route_params' => $request->attributes->get('_route_params'), 'q' => $card->getCode(), 'view' => 'card', 'sort' => 'set', 'pagetitle' => $card->getName(), 'meta' => $meta, 'selected_pack_code' => $selectedPackCode]);
    }

    /**
     * @Route(
     *     "/set/{pack_code}/{view}/{sort}/{page}",
     *     name="cards_list",
     *     defaults={"view"="list", "sort"="set", "page"=1}
     * )
     */
    public function listAction($pack_code, $view, $sort, $page, Request $request): Response
    {
        $pack = $this->packRepository->findOneBy(['code' => $pack_code]);
        if (!$pack) {
            throw $this->createNotFoundException('This pack does not exist');
        }
        $game_name = $this->gameName;
        $publisher_name = $this->publisherName;
        $meta = $pack->getName().", a set of cards for {$game_name}".($pack->getDateRelease() ? ' published on '.$pack->getDateRelease()->format('Y/m/d') : '')." by {$publisher_name}.";
        $key = array_search('pack', SearchController::$searchKeys);

        return $this->forward(DisplaySearchController::class, ['_route' => $request->attributes->get('_route'), '_route_params' => $request->attributes->get('_route_params'), 'q' => $key.':'.$pack_code, 'view' => $view, 'sort' => $sort, 'page' => $page, 'pagetitle' => $pack->getName(), 'meta' => $meta]);
    }

    /**
     * @Route(
     *     "/cycle/{cycle_code}/{view}/{sort}/{page}",
     *     name="cards_cycle",
     *     defaults={"view"="list", "sort"="sphere", "page"=1}
     * )
     */
    public function cycleAction($cycle_code, $view, $sort, $page, Request $request): Response
    {
        $cycle = $this->cycleRepository->findOneBy(['code' => $cycle_code]);
        if (!$cycle) {
            throw $this->createNotFoundException('This cycle does not exist');
        }
        $game_name = $this->gameName;
        $publisher_name = $this->publisherName;
        $meta = $cycle->getName().", a cycle of adventure packs for {$game_name} published by {$publisher_name}.";
        $key = array_search('cycle', SearchController::$searchKeys);

        return $this->forward(DisplaySearchController::class, ['_route' => $request->attributes->get('_route'), '_route_params' => $request->attributes->get('_route_params'), 'q' => $key.':'.$cycle_code, 'view' => $view, 'sort' => $sort, 'page' => $page, 'pagetitle' => $cycle->getName(), 'meta' => $meta]);
    }

    /**
     * Processes the action of the card search form.
     *
     * @Route("/process", name="cards_processSearchForm")
     */
    public function processAction(Request $request): RedirectResponse
    {
        $view = $request->query->get('view') ?: 'list';
        $sort = $request->query->get('sort') ?: 'name';
        $operators = [':', '!', '<', '>'];
        $spheres = $this->sphereRepository->findAll();
        $params = [];
        if ('' != $request->query->get('q')) {
            $params[] = $request->query->get('q');
        }
        foreach (SearchController::$searchKeys as $key => $searchName) {
            $val = $request->query->get($key);
            if (isset($val) && '' != $val) {
                if (is_array($val)) {
                    if ('sphere' === $searchName && count($val) === count($spheres)) {
                        continue;
                    }
                    $params[] = $key.':'.implode('|', array_map(fn ($s) => false !== strstr($s, ' ') ? "\"{$s}\"" : $s, $val));
                } else {
                    if ('date_release' == $searchName) {
                        $op = '';
                    } else {
                        if (!preg_match('/^[\\p{L}\\p{N}\\_\\-\\&]+$/u', $val, $match)) {
                            $val = "\"{$val}\"";
                        }
                        $op = $request->query->get($key.'o');
                        if (!in_array($op, $operators)) {
                            $op = ':';
                        }
                    }
                    $params[] = "{$key}{$op}{$val}";
                }
            }
        }
        $find = ['q' => implode(' ', $params)];
        if ('name' != $sort) {
            $find['sort'] = $sort;
        }
        if ('list' != $view) {
            $find['view'] = $view;
        }

        return $this->redirect($this->generateUrl('cards_find').'?'.http_build_query($find));
    }

    /**
     * Processes the action of the single card search input.
     *
     * @return RedirectResponse|Response
     *
     * @Route("/find", name="cards_find")
     */
    public function findAction(Request $request)
    {
        $q = $request->query->get('q');
        $q = str_replace('t:campaign', 't:treasure', $q);
        $page = $request->query->get('page') ?: 1;
        $view = $request->query->get('view') ?: 'list';
        $sort = $request->query->get('sort') ?: 'name';
        // we may be able to redirect to a better url if the search is on a single set
        $conditions = $this->cardsData->syntax($q);
        if (1 == count($conditions) && 3 == count($conditions[0]) && ':' == $conditions[0][1]) {
            if ($conditions[0][0] == array_search('pack', SearchController::$searchKeys)) {
                $url = $this->generateUrl('cards_list', ['pack_code' => $conditions[0][2], 'view' => $view, 'sort' => $sort, 'page' => $page]);

                return $this->redirect($url);
            }
            if ($conditions[0][0] == array_search('cycle', SearchController::$searchKeys)) {
                $url = $this->generateUrl('cards_cycle', ['cycle_code' => $conditions[0][2], 'view' => $view, 'sort' => $sort, 'page' => $page]);

                return $this->redirect($url);
            }
        }

        return $this->forward(DisplaySearchController::class, ['q' => $q, 'view' => $view, 'sort' => $sort, 'page' => $page, '_route' => $request->get('_route')]);
    }
}
