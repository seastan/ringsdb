<?php

namespace AppBundle\Controller;

use AppBundle\Repository\TypeRepository;
use AppBundle\Repository\SphereRepository;
use AppBundle\Repository\PackRepository;
use AppBundle\Repository\CycleRepository;
use AppBundle\Repository\CardPrintingRepository;
use AppBundle\Repository\CardRepository;
use AppBundle\Entity\CardPrinting;
use AppBundle\Entity\Card;
use AppBundle\Services\CardsData;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;

class SearchController extends AbstractController {
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

    public function __construct(CardsData $cardsData, int $cacheExpiration, ?string $gameName, ?string $publisherName, CardPrintingRepository $cardPrintingRepository, CycleRepository $cycleRepository, PackRepository $packRepository, SphereRepository $sphereRepository) {
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
    public static $searchKeys = [
        '' => 'code',
        'a' => 'attack',
        'b' => 'threat',
        'c' => 'cycle',
        'd' => 'defense',
        'e' => 'pack',
        'f' => 'flavor',
        'h' => 'health',
        'i' => 'illustrator',
        'k' => 'traits',
        'o' => 'cost',
        's' => 'sphere',
        't' => 'type',
        'u' => 'isUnique',
        'w' => 'willpower',
        'x' => 'text',
        'y' => 'quantity',
        'z' => 'hasErrata',
    ];
    /**
     * @var array<string, string>
     */
    public static $searchTypes = [
        '' => 'string',
        'f' => 'string',
        'i' => 'string',
        'k' => 'string',
        'x' => 'string',
        'e' => 'code',
        's' => 'code',
        't' => 'code',
        'c' => 'code',
        'a' => 'integer',
        'b' => 'integer',
        'd' => 'integer',
        'h' => 'integer',
        'o' => 'integer',
        'w' => 'integer',
        'y' => 'integer',
        'u' => 'boolean',
        'z' => 'boolean',
    ];

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function formAction(TypeRepository $typeRepository) {
        $response = new Response();
        $response->setPublic();
        $response->setMaxAge($this->cacheExpiration);

        $dbh = $this->getDoctrine()->getConnection();

        $list_packs = $this->packRepository->findBy([], ["dateRelease" => "ASC", "position" => "ASC"]);
        $packs = [];
        foreach ($list_packs as $pack) {
            /* @var $pack \AppBundle\Entity\Pack */
            $packs[] = [
                "name" => $pack->getName(),
                "code" => $pack->getCode(),
            ];
        }

        $list_cycles = $this->cycleRepository->findBy([], ["position" => "ASC"]);
        $cycles = [];
        foreach ($list_cycles as $cycle) {
            /* @var $cycle \AppBundle\Entity\Cycle */
            $cycles[] = [
                "name" => $cycle->getName(),
                "code" => $cycle->getCode(),
            ];
        }

        $types = $typeRepository->findBy([], ["name" => "ASC"]);
        $spheres = $this->sphereRepository->findBy([], ["id" => "ASC"]);

        $traits = $this->cardsData->getDistinctTraits();
        $traits = array_filter(array_keys($traits));
        sort($traits);

        $list_illustrators = $dbh->executeQuery("SELECT DISTINCT illustrator FROM card_printing WHERE illustrator IS NOT NULL AND illustrator != '' ORDER BY illustrator")->fetchAll();
        $illustrators = array_map(function($card) {
            return $card["illustrator"];
        }, $list_illustrators);

        return $this->render('AppBundle:Search:searchform.html.twig', [
            "pagetitle" => "Card Search",
            "pagedescription" => "Find all the cards of the game, easily searchable.",
            "packs" => $packs,
            "cycles" => $cycles,
            "types" => $types,
            "spheres" => $spheres,
            "traits" => $traits,
            "illustrators" => $illustrators,
            "allsets" => $this->renderView('AppBundle:Default:allsets.html.twig', [
                "data" => $this->cardsData->allSetsData(),
            ]),

        ], $response);
    }

    /**
     * @param mixed $card_code
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function zoomAction($card_code, Request $request, CardRepository $cardRepository) {
        $card = $cardRepository->findOneBy(["code" => $card_code]);
        if (!$card) {
            throw $this->createNotFoundException('Sorry, this card is not in the database (yet?)');
        }

        $game_name = $this->gameName;
        $publisher_name = $this->publisherName;

        $meta = $card->getName() . ", a " . $card->getSphere()->getName() . " " . $card->getType()->getName() . " card for $game_name from the set " . $card->getPack()->getName() . " published by $publisher_name.";

        $selectedPackCode = $request->query->get('pack', null);

        return $this->forward('AppBundle:Search:display', [
            '_route' => $request->attributes->get('_route'),
            '_route_params' => $request->attributes->get('_route_params'),
            'q' => $card->getCode(),
            'view' => 'card',
            'sort' => 'set',
            'pagetitle' => $card->getName(),
            'meta' => $meta,
            'selected_pack_code' => $selectedPackCode,
        ]);
    }

    /**
     * @param mixed $pack_code
     * @param mixed $view
     * @param mixed $sort
     * @param mixed $page
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function listAction($pack_code, $view, $sort, $page, Request $request) {
        $pack = $this->packRepository->findOneBy(['code' => $pack_code]);

        if (!$pack) {
            throw $this->createNotFoundException('This pack does not exist');
        }

        $game_name = $this->gameName;
        $publisher_name = $this->publisherName;

        $meta = $pack->getName() . ", a set of cards for $game_name" . ($pack->getDateRelease() ? " published on " . $pack->getDateRelease()->format('Y/m/d') : "") . " by $publisher_name.";

        $key = array_search('pack', SearchController::$searchKeys);

        return $this->forward('AppBundle:Search:display', [
            '_route' => $request->attributes->get('_route'),
            '_route_params' => $request->attributes->get('_route_params'),
            'q' => $key . ':' . $pack_code,
            'view' => $view,
            'sort' => $sort,
            'page' => $page,
            'pagetitle' => $pack->getName(),
            'meta' => $meta
        ]);
    }

    /**
     * @param mixed $cycle_code
     * @param mixed $view
     * @param mixed $sort
     * @param mixed $page
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function cycleAction($cycle_code, $view, $sort, $page, Request $request) {
        $cycle = $this->cycleRepository->findOneBy(["code" => $cycle_code]);

        if (!$cycle) {
            throw $this->createNotFoundException('This cycle does not exist');
        }

        $game_name = $this->gameName;
        $publisher_name = $this->publisherName;

        $meta = $cycle->getName() . ", a cycle of adventure packs for $game_name published by $publisher_name.";

        $key = array_search('cycle', SearchController::$searchKeys);

        return $this->forward('AppBundle:Search:display', [
            '_route' => $request->attributes->get('_route'),
            '_route_params' => $request->attributes->get('_route_params'),
            'q' => $key . ':' . $cycle_code,
            'view' => $view,
            'sort' => $sort,
            'page' => $page,
            'pagetitle' => $cycle->getName(),
            'meta' => $meta,
        ]);
    }

    /**
     * Processes the action of the card search form
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function processAction(Request $request) {
        $view = $request->query->get('view') ?: 'list';
        $sort = $request->query->get('sort') ?: 'name';

        $operators = [":", "!", "<", ">"];
        $spheres = $this->sphereRepository->findAll();

        $params = [];

        if ($request->query->get('q') != "") {
            $params[] = $request->query->get('q');
        }

        foreach (SearchController::$searchKeys as $key => $searchName) {
            $val = $request->query->get($key);
            if (isset($val) && $val != "") {
                if (is_array($val)) {
                    if ($searchName == "sphere" && count($val) == count($spheres)) {
                        continue;
                    }
                    $params[] = $key . ":" . implode("|", array_map(function($s) {
                            return strstr($s, " ") !== false ? "\"$s\"" : $s;
                        }, $val));
                } else {
                    if ($searchName == "date_release") {
                        $op = "";
                    } else {
                        if (!preg_match('/^[\p{L}\p{N}\_\-\&]+$/u', $val, $match)) {
                            $val = "\"$val\"";
                        }
                        $op = $request->query->get($key . "o");
                        if (!in_array($op, $operators)) {
                            $op = ":";
                        }
                    }
                    $params[] = "$key$op$val";
                }
            }
        }

        $find = ['q' => implode(" ", $params)];

        if ($sort != "name") {
            $find['sort'] = $sort;
        }

        if ($view != "list") {
            $find['view'] = $view;
        }

        return $this->redirect($this->generateUrl('cards_find') . '?' . http_build_query($find));
    }

    /**
     * Processes the action of the single card search input
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function findAction(Request $request) {
        $q = $request->query->get('q');
        $q = str_replace('t:campaign', 't:treasure', $q);
        $page = $request->query->get('page') ?: 1;
        $view = $request->query->get('view') ?: 'list';
        $sort = $request->query->get('sort') ?: 'name';

        // we may be able to redirect to a better url if the search is on a single set
        $conditions = $this->cardsData->syntax($q);
        if (count($conditions) == 1 && count($conditions[0]) == 3 && $conditions[0][1] == ":") {
            if ($conditions[0][0] == array_search('pack', SearchController::$searchKeys)) {
                $url = $this->generateUrl('cards_list', ['pack_code' => $conditions[0][2], 'view' => $view, 'sort' => $sort, 'page' => $page]);

                return $this->redirect($url);
            }

            if ($conditions[0][0] == array_search('cycle', SearchController::$searchKeys)) {
                $url = $this->generateUrl('cards_cycle', ['cycle_code' => $conditions[0][2], 'view' => $view, 'sort' => $sort, 'page' => $page]);

                return $this->redirect($url);
            }
        }

        return $this->forward('AppBundle:Search:display', [
            'q' => $q,
            'view' => $view,
            'sort' => $sort,
            'page' => $page,
            '_route' => $request->get('_route')
        ]);
    }

    /**
     * @param mixed $q
     * @param string $view
     * @param mixed $sort
     * @param int $page
     * @param string $pagetitle
     * @param string $meta
     * @param mixed $selected_pack_code
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function displayAction($q, $view = 'card', $sort, $page = 1, $pagetitle = '', $meta = '', $selected_pack_code = null) {
        $response = new Response();
        $response->setPublic();
        $response->setMaxAge($this->cacheExpiration);

        static $availability = [];

        $cards = [];
        $first = 0;
        $last = 0;
        $pagination = '';

        $pagesizes = [
            'list' => 240,
            'spoiler' => 240,
            'card' => 20,
            'scan' => 20,
            'short' => 1000,
        ];
        $includeReviews = false;

        if (!array_key_exists($view, $pagesizes)) {
            $view = 'list';
        }

        $conditions = $this->cardsData->syntax($q);
        $conditions = $this->cardsData->validateConditions($conditions);

        $q = $this->cardsData->buildQueryFromConditions($conditions);
        if ($q && $rows = $this->cardsData->get_search_rows($conditions, $sort)) {
            if (count($rows) == 1) {
                $view = 'card';
                $includeReviews = true;
            }

            if ($pagetitle == '') {
                if (count($conditions) == 1 && count($conditions[0]) == 3 && $conditions[0][1] == ":") {
                    if ($conditions[0][0] == "e") {
                        $pack = $this->packRepository->findOneBy(["code" => $conditions[0][2]]);

                        if ($pack) {
                            $pagetitle = $pack->getName();
                        }
                    }

                    if ($conditions[0][0] == "c") {
                        $cycle = $this->cycleRepository->findOneBy(["code" => $conditions[0][2]]);

                        if ($cycle) {
                            $pagetitle = $cycle->getName();
                        }
                    }
                }
            }

            // pagination
            $nb_per_page = $pagesizes[$view];
            $first = $nb_per_page * ($page - 1);
            if ($first > count($rows)) {
                $page = 1;
                $first = 0;
            }
            $last = $first + $nb_per_page;

            // data à passer à la view
            for ($rowindex = $first; $rowindex < $last && $rowindex < count($rows); $rowindex++) {
                /* @var $card \AppBundle\Entity\Card */
                $card = $rows[$rowindex];
                /* @var $pack \AppBundle\Entity\Pack */
                $pack = $card->getPack();
                /** @var array<string, mixed> $cardinfo */
                $cardinfo = $this->cardsData->getCardInfo($card, false);

                if (empty($availability[$pack->getCode()])) {
                    $availability[$pack->getCode()] = false;
                    if ($pack->getDateRelease() && $pack->getDateRelease() <= new \DateTime()) {
                        $availability[$pack->getCode()] = true;
                    }
                }

                $cardinfo['available'] = $availability[$pack->getCode()];
                $cardinfo['selected_pack_code'] = $selected_pack_code;

                if ($selected_pack_code) {
                    foreach ($cardinfo['packs'] as $p) {
                        if ($p['pack_code'] === $selected_pack_code && !empty($p['imagesrc'])) {
                            $cardinfo['imagesrc'] = $p['imagesrc'];
                            break;
                        }
                    }
                }

                if ($includeReviews) {
                    $cardinfo['reviews'] = $this->cardsData->get_reviews($card);
                }
                $cards[] = $cardinfo;
            }

            $first += 1;

            // si on a des cartes on affiche une bande de navigation/pagination
            if (count($rows) == 1) {
                $pagination = $this->setnavigation($rows[0], $selected_pack_code);
            } else {
                $pagination = $this->pagination($nb_per_page, count($rows), $first, $q, $view, $sort);
            }

            // si on est en vue "short" on casse la liste par tri
            if (count($cards) && $view == "short") {
                $sortfields = [
                    'set' => 'pack_name',
                    'name' => 'name',
                    'sphere' => 'sphere_name',
                    'type' => 'type_name',
                    'cost' => 'cost'
                ];

                $brokenlist = [];
                for ($i = 0; $i < count($cards); $i++) {
                    $val = $cards[$i][$sortfields[$sort]];

                    if ($sort == "name") {
                        $val = substr($val, 0, 1);
                    }

                    if (!isset($brokenlist[$val])) {
                        $brokenlist[$val] = [];
                    }

                    array_push($brokenlist[$val], $cards[$i]);
                }

                $cards = $brokenlist;
            }
        }

        $searchbar = $this->renderView('AppBundle:Search:searchbar.html.twig', [
            'q' => $q,
            'view' => $view,
            'sort' => $sort,
        ]);

        if (empty($pagetitle)) {
            $pagetitle = $q;
        }

        // attention si $s="short", $cards est un tableau à 2 niveaux au lieu de 1 seul
        return $this->render('AppBundle:Search:display-' . $view . '.html.twig', [
            'view' => $view,
            'sort' => $sort,
            'cards' => $cards,
            'first' => $first,
            'last' => $last,
            'searchbar' => $searchbar,
            'pagination' => $pagination,
            'pagetitle' => $pagetitle,
            'metadescription' => $meta,
            'includeReviews' => $includeReviews
        ], $response);
    }

    /**
     * @param mixed $card
     * @param mixed $selectedPackCode
     * @return string
     */
    public function setnavigation($card, $selectedPackCode = null) {
        $em = $this->getDoctrine();

        $selectedPack = null;
        if ($selectedPackCode) {
            $selectedPack = $this->packRepository->findOneBy(['code' => $selectedPackCode]);
        }

        if ($selectedPack) {
            // Navigate within the selected printing's pack via CardPrinting positions.
            $printing = $this->cardPrintingRepository->findOneBy(['card' => $card, 'pack' => $selectedPack]);
            if ($printing) {
                $pos = $printing->getPosition();
                $prevPrinting = $this->cardPrintingRepository->findOneBy(['pack' => $selectedPack, 'position' => $pos - 1]);
                $nextPrinting = $this->cardPrintingRepository->findOneBy(['pack' => $selectedPack, 'position' => $pos + 1]);
                $prev = $prevPrinting ? $prevPrinting->getCard() : null;
                $next = $nextPrinting ? $nextPrinting->getCard() : null;
            } else {
                $prev = null;
                $next = null;
            }
        } else {
            $primaryPrinting = $card->getPrimaryPrinting();
            $selectedPack    = $primaryPrinting ? $primaryPrinting->getPack() : null;
            if ($primaryPrinting && $selectedPack) {
                $pos  = $primaryPrinting->getPosition();
                $prevP = $this->cardPrintingRepository->findOneBy(['pack' => $selectedPack, 'position' => $pos - 1]);
                $nextP = $this->cardPrintingRepository->findOneBy(['pack' => $selectedPack, 'position' => $pos + 1]);
                $prev  = $prevP ? $prevP->getCard() : null;
                $next  = $nextP ? $nextP->getCard() : null;
            } else {
                $prev = null;
                $next = null;
            }
        }

        $packParam = $selectedPackCode ? ['pack' => $selectedPackCode] : [];

        return $this->renderView('AppBundle:Search:setnavigation.html.twig', [
            "prevtitle" => $prev ? $prev->getName() : "",
            "prevhref" => $prev ? $this->generateUrl('cards_zoom', array_merge(['card_code' => $prev->getCode()], $packParam)) : "",
            "nexttitle" => $next ? $next->getName() : "",
            "nexthref" => $next ? $this->generateUrl('cards_zoom', array_merge(['card_code' => $next->getCode()], $packParam)) : "",
            "settitle" => $selectedPack->getName(),
            "sethref" => $this->generateUrl('cards_list', ['pack_code' => $selectedPack->getCode()]),
        ]);
    }

    /**
     * @param mixed $q
     * @param mixed $v
     * @param mixed $s
     * @param mixed $ps
     * @param mixed $pi
     * @param mixed $total
     * @return string
     */
    public function paginationItem($q = null, $v, $s, $ps, $pi, $total) {
        return $this->renderView('AppBundle:Search:paginationitem.html.twig', [
            "href" => $q == null ? "" : $this->generateUrl('cards_find', ['q' => $q, 'view' => $v, 'sort' => $s, 'page' => $pi]),
            "ps" => $ps,
            "pi" => $pi,
            "s" => $ps * ($pi - 1) + 1,
            "e" => min($ps * $pi, $total),
        ]);
    }

    /**
     * @param mixed $pagesize
     * @param mixed $total
     * @param mixed $current
     * @param mixed $q
     * @param mixed $view
     * @param mixed $sort
     * @return string
     */
    public function pagination($pagesize, $total, $current, $q, $view, $sort) {
        if ($total < $pagesize) {
            $pagesize = $total;
        }

        $pagecount = ceil($total / $pagesize);
        $pageindex = ceil($current / $pagesize); #1-based

        $first = "";
        if ($pageindex > 2) {
            $first = $this->paginationItem($q, $view, $sort, $pagesize, 1, $total);
        }

        $prev = "";
        if ($pageindex > 1) {
            $prev = $this->paginationItem($q, $view, $sort, $pagesize, $pageindex - 1, $total);
        }

        $current = $this->paginationItem(null, $view, $sort, $pagesize, $pageindex, $total);

        $next = "";
        if ($pageindex < $pagecount) {
            $next = $this->paginationItem($q, $view, $sort, $pagesize, $pageindex + 1, $total);
        }

        $last = "";
        if ($pageindex < $pagecount - 1) {
            $last = $this->paginationItem($q, $view, $sort, $pagesize, $pagecount, $total);
        }

        return $this->renderView('AppBundle:Search:pagination.html.twig', [
            "first" => $first,
            "prev" => $prev,
            "current" => $current,
            "next" => $next,
            "last" => $last,
            "count" => $total,
            "ellipsisbefore" => $pageindex > 3,
            "ellipsisafter" => $pageindex < $pagecount - 2,
        ]);
    }
}
