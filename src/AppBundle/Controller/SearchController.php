<?php

namespace AppBundle\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class SearchController extends Controller {

	public static $searchKeys = array(
		''  => 'code',
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
		'r' => 'date_release',
		's' => 'sphere',
		't' => 'type',
		'u' => 'isUnique',
		'w' => 'willpower',
		'x' => 'text',
		'y' => 'quantity',
	);

	public static $searchTypes = array(
		''  => 'string',
		'f' => 'string',
		'i' => 'string',
		'k' => 'string',
		'x' => 'string',
        'r' => 'date',
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
	);

	public function formAction() {
		$response = new Response();
		$response->setPublic();
		$response->setMaxAge($this->container->getParameter('cache_expiration'));

		$dbh = $this->getDoctrine()->getConnection();

		$list_packs = $this->getDoctrine()->getRepository('AppBundle:Pack')->findBy([], ["dateRelease" => "ASC", "position" => "ASC"]);
		$packs = [];
		foreach ($list_packs as $pack) {
			$packs[] = [
				"name" => $pack->getName(),
				"code" => $pack->getCode(),
			];
		}

		$list_cycles = $this->getDoctrine()->getRepository('AppBundle:Cycle')->findBy([], ["position" => "ASC"]);
		$cycles = [];
		foreach ($list_cycles as $cycle) {
			$cycles[] = [
				"name" => $cycle->getName(),
				"code" => $cycle->getCode(),
			];
		}

		$types = $this->getDoctrine()->getRepository('AppBundle:Type')->findBy([], ["name" => "ASC"]);
		$spheres = $this->getDoctrine()->getRepository('AppBundle:Sphere')->findBy([], ["id" => "ASC"]);

		$traits = $this->get('cards_data')->getDistinctTraits();
		$traits = array_filter(array_keys($traits));
		sort($traits);

		$list_illustrators = $dbh->executeQuery("SELECT DISTINCT c.illustrator FROM card c WHERE c.illustrator != '' ORDER BY c.illustrator")->fetchAll();
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
				"data" => $this->get('cards_data')->allSetsData(),
			]),

		], $response);
	}

    public function zoomAction($card_code, Request $request) {
        $card = $this->getDoctrine()->getRepository('AppBundle:Card')->findOneBy(["code" => $card_code]);
        if (!$card) {
            throw $this->createNotFoundException('Sorry, this card is not in the database (yet?)');
        }

        $game_name = $this->container->getParameter('game_name');
        $publisher_name = $this->container->getParameter('publisher_name');

        $meta = $card->getName() . ", a " . $card->getSphere()->getName() . " " . $card->getType()->getName() . " card for $game_name from the set " . $card->getPack()->getName() . " published by $publisher_name.";

        return $this->forward('AppBundle:Search:display', [
            '_route' => $request->attributes->get('_route'),
            '_route_params' => $request->attributes->get('_route_params'),
            'q' => $card->getCode(),
            'view' => 'card',
            'sort' => 'set',
            'pagetitle' => $card->getName(),
            'meta' => $meta
        ]);
    }

    public function listAction($pack_code, $view, $sort, $page, Request $request) {
        $pack = $this->getDoctrine()->getRepository('AppBundle:Pack')->findOneBy(['code' => $pack_code]);

        if (!$pack) {
            throw $this->createNotFoundException('This pack does not exist');
        }

        $game_name = $this->container->getParameter('game_name');
        $publisher_name = $this->container->getParameter('publisher_name');

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

    public function cycleAction($cycle_code, $view, $sort, $page, Request $request) {
        $cycle = $this->getDoctrine()->getRepository('AppBundle:Cycle')->findOneBy(["code" => $cycle_code]);

        if (!$cycle) {
            throw $this->createNotFoundException('This cycle does not exist');
        }

        $game_name = $this->container->getParameter('game_name');
        $publisher_name = $this->container->getParameter('publisher_name');

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
        $spheres = $this->getDoctrine()->getRepository('AppBundle:Sphere')->findAll();

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
        $page = $request->query->get('page') ?: 1;
        $view = $request->query->get('view') ?: 'list';
        $sort = $request->query->get('sort') ?: 'name';

        // we may be able to redirect to a better url if the search is on a single set
        $conditions = $this->get('cards_data')->syntax($q);
        if (count($conditions) == 1 && count($conditions[0]) == 3 && $conditions[0][1] == ":") {
            if ($conditions[0][0] == array_search('pack', SearchController::$searchKeys)) {
                $url = $this->get('router')->generate('cards_list', ['pack_code' => $conditions[0][2], 'view' => $view, 'sort' => $sort, 'page' => $page]);

                return $this->redirect($url);
            }

            if ($conditions[0][0] == array_search('cycle', SearchController::$searchKeys)) {
                $url = $this->get('router')->generate('cards_cycle', ['cycle_code' => $conditions[0][2], 'view' => $view, 'sort' => $sort, 'page' => $page]);
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

    public function displayAction($q, $view = 'card', $sort, $page = 1, $pagetitle = '', $meta = '', Request $request) {
        $response = new Response();
        $response->setPublic();
        $response->setMaxAge($this->container->getParameter('cache_expiration'));

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

        $conditions = $this->get('cards_data')->syntax($q);
        $conditions = $this->get('cards_data')->validateConditions($conditions);

        $q = $this->get('cards_data')->buildQueryFromConditions($conditions);
        if ($q && $rows = $this->get('cards_data')->get_search_rows($conditions, $sort)) {
            if (count($rows) == 1) {
                $view = 'card';
                $includeReviews = true;
            }

            if ($pagetitle == '') {
                if (count($conditions) == 1 && count($conditions[0]) == 3 && $conditions[0][1] == ":") {
                    if ($conditions[0][0] == "e") {
                        $pack = $this->getDoctrine()->getRepository('AppBundle:Pack')->findOneBy(["code" => $conditions[0][2]]);

                        if ($pack) {
                            $pagetitle = $pack->getName();
                        }
                    }

                    if ($conditions[0][0] == "c") {
                        $cycle = $this->getDoctrine()->getRepository('AppBundle:Cycle')->findOneBy(["code" => $conditions[0][2]]);

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
                $card = $rows[$rowindex];
                $pack = $card->getPack();
                $cardinfo = $this->get('cards_data')->getCardInfo($card, false);

                if (empty($availability[$pack->getCode()])) {
                    $availability[$pack->getCode()] = false;
                    if ($pack->getDateRelease() && $pack->getDateRelease() <= new \DateTime()) {
                        $availability[$pack->getCode()] = true;
                    }
                }

                $cardinfo['available'] = $availability[$pack->getCode()];
                if ($includeReviews) {
                    $cardinfo['reviews'] = $this->get('cards_data')->get_reviews($card);
                }
                $cards[] = $cardinfo;
            }

            $first += 1;

            // si on a des cartes on affiche une bande de navigation/pagination
            if (count($rows)) {
                if (count($rows) == 1) {
                    $pagination = $this->setnavigation($card, $q, $view, $sort);
                } else {
                    $pagination = $this->pagination($nb_per_page, count($rows), $first, $q, $view, $sort);
                }
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

    public function setnavigation($card, $q, $view, $sort) {
        $em = $this->getDoctrine();
        $prev = $em->getRepository('AppBundle:Card')->findOneBy(["pack" => $card->getPack(), "position" => $card->getPosition() - 1]);
        $next = $em->getRepository('AppBundle:Card')->findOneBy(["pack" => $card->getPack(), "position" => $card->getPosition() + 1]);

        return $this->renderView('AppBundle:Search:setnavigation.html.twig', [
            "prevtitle" => $prev ? $prev->getName() : "",
            "prevhref" => $prev ? $this->get('router')->generate('cards_zoom', ['card_code' => $prev->getCode()]) : "",
            "nexttitle" => $next ? $next->getName() : "",
            "nexthref" => $next ? $this->get('router')->generate('cards_zoom', ['card_code' => $next->getCode()]) : "",
            "settitle" => $card->getPack()->getName(),
            "sethref" => $this->get('router')->generate('cards_list', ['pack_code' => $card->getPack()->getCode()]),
        ]);
    }

    public function paginationItem($q = null, $v, $s, $ps, $pi, $total) {
        return $this->renderView('AppBundle:Search:paginationitem.html.twig', [
            "href" => $q == null ? "" : $this->get('router')->generate('cards_find', ['q' => $q, 'view' => $v, 'sort' => $s, 'page' => $pi]),
            "ps" => $ps,
            "pi" => $pi,
            "s" => $ps * ($pi - 1) + 1,
            "e" => min($ps * $pi, $total),
        ]);
    }

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
