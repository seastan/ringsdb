<?php

declare(strict_types=1);

namespace App\Controller\CardSearch;

use App\Entity\Pack;
use App\Repository\CardPrintingRepository;
use App\Repository\CycleRepository;
use App\Repository\PackRepository;
use App\Services\CardsData;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

class DisplaySearchController extends AbstractController
{
    private int $cacheExpiration;
    private CardsData $cardsData;
    private PackRepository $packRepository;
    private CycleRepository $cycleRepository;
    private CardPrintingRepository $cardPrintingRepository;

    public function __construct(
        int $cacheExpiration,
        CardsData $cardsData,
        PackRepository $packRepository,
        CycleRepository $cycleRepository,
        CardPrintingRepository $cardPrintingRepository
    ) {
        $this->cacheExpiration = $cacheExpiration;
        $this->cardsData = $cardsData;
        $this->packRepository = $packRepository;
        $this->cycleRepository = $cycleRepository;
        $this->cardPrintingRepository = $cardPrintingRepository;
    }

    /**
     * No route for this controller, it's called internally only.
     *
     * @param string $view
     * @param int    $page
     * @param string $pagetitle
     * @param string $meta
     */
    public function __invoke($q, $view = 'card', $sort, $page = 1, $pagetitle = '', $meta = '', $selected_pack_code = null): Response
    {
        $response = new Response();
        $response->setPublic();
        $response->setMaxAge($this->cacheExpiration);
        static $availability = [];
        $cards = [];
        $first = 0;
        $last = 0;
        $pagination = '';
        $pagesizes = ['list' => 240, 'spoiler' => 240, 'card' => 20, 'scan' => 20, 'short' => 1000];
        $includeReviews = false;
        if (!array_key_exists($view, $pagesizes)) {
            $view = 'list';
        }
        $conditions = $this->cardsData->syntax($q);
        $conditions = $this->cardsData->validateConditions($conditions);
        $q = $this->cardsData->buildQueryFromConditions($conditions);
        if ($q && ($rows = $this->cardsData->get_search_rows($conditions, $sort))) {
            if (1 == count($rows)) {
                $view = 'card';
                $includeReviews = true;
            }
            if ('' == $pagetitle) {
                if (1 == count($conditions) && 3 == count($conditions[0]) && ':' == $conditions[0][1]) {
                    if ('e' == $conditions[0][0]) {
                        $pack = $this->packRepository->findOneBy(['code' => $conditions[0][2]]);
                        if ($pack) {
                            $pagetitle = $pack->getName();
                        }
                    }
                    if ('c' == $conditions[0][0]) {
                        $cycle = $this->cycleRepository->findOneBy(['code' => $conditions[0][2]]);
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
            for ($rowindex = $first; $rowindex < $last && $rowindex < count($rows); ++$rowindex) {
                /* @var $card \App\Entity\Card */
                $card = $rows[$rowindex];
                /* @var $pack Pack */
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
            ++$first;
            // si on a des cartes on affiche une bande de navigation/pagination
            if (1 == count($rows)) {
                $pagination = $this->setNavigation($rows[0], $selected_pack_code);
            } else {
                $pagination = $this->pagination($nb_per_page, count($rows), $first, $q, $view, $sort);
            }
            // si on est en vue "short" on casse la liste par tri
            if (count($cards) && 'short' == $view) {
                $sortfields = ['set' => 'pack_name', 'name' => 'name', 'sphere' => 'sphere_name', 'type' => 'type_name', 'cost' => 'cost'];
                $brokenlist = [];
                for ($i = 0; $i < count($cards); ++$i) {
                    $val = $cards[$i][$sortfields[$sort]];
                    if ('name' == $sort) {
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
        $searchbar = $this->renderView('Search/searchbar.html.twig', ['q' => $q, 'view' => $view, 'sort' => $sort]);
        if (empty($pagetitle)) {
            $pagetitle = $q;
        }

        // attention si $s="short", $cards est un tableau à 2 niveaux au lieu de 1 seul
        return $this->render('Search/display-'.$view.'.html.twig', ['view' => $view, 'sort' => $sort, 'cards' => $cards, 'first' => $first, 'last' => $last, 'searchbar' => $searchbar, 'pagination' => $pagination, 'pagetitle' => $pagetitle, 'metadescription' => $meta, 'includeReviews' => $includeReviews], $response);
    }

    public function setNavigation($card, $selectedPackCode = null): string
    {
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
            $selectedPack = $primaryPrinting ? $primaryPrinting->getPack() : null;
            if ($primaryPrinting && $selectedPack) {
                $pos = $primaryPrinting->getPosition();
                $prevP = $this->cardPrintingRepository->findOneBy(['pack' => $selectedPack, 'position' => $pos - 1]);
                $nextP = $this->cardPrintingRepository->findOneBy(['pack' => $selectedPack, 'position' => $pos + 1]);
                $prev = $prevP ? $prevP->getCard() : null;
                $next = $nextP ? $nextP->getCard() : null;
            } else {
                $prev = null;
                $next = null;
            }
        }
        $packParam = $selectedPackCode ? ['pack' => $selectedPackCode] : [];

        return $this->renderView('Search/setnavigation.html.twig', ['prevtitle' => $prev ? $prev->getName() : '', 'prevhref' => $prev ? $this->generateUrl('cards_zoom', array_merge(['card_code' => $prev->getCode()], $packParam)) : '', 'nexttitle' => $next ? $next->getName() : '', 'nexthref' => $next ? $this->generateUrl('cards_zoom', array_merge(['card_code' => $next->getCode()], $packParam)) : '', 'settitle' => $selectedPack->getName(), 'sethref' => $this->generateUrl('cards_list', ['pack_code' => $selectedPack->getCode()])]);
    }

    public function paginationItem($q = null, $v, $s, $ps, $pi, $total): string
    {
        return $this->renderView('Search/paginationitem.html.twig', ['href' => null == $q ? '' : $this->generateUrl('cards_find', ['q' => $q, 'view' => $v, 'sort' => $s, 'page' => $pi]), 'ps' => $ps, 'pi' => $pi, 's' => $ps * ($pi - 1) + 1, 'e' => min($ps * $pi, $total)]);
    }

    public function pagination($pagesize, $total, $current, $q, $view, $sort): string
    {
        if ($total < $pagesize) {
            $pagesize = $total;
        }
        $pagecount = ceil($total / $pagesize);
        $pageindex = ceil($current / $pagesize);
        // 1-based
        $first = '';
        if ($pageindex > 2) {
            $first = $this->paginationItem($q, $view, $sort, $pagesize, 1, $total);
        }
        $prev = '';
        if ($pageindex > 1) {
            $prev = $this->paginationItem($q, $view, $sort, $pagesize, $pageindex - 1, $total);
        }
        $current = $this->paginationItem(null, $view, $sort, $pagesize, $pageindex, $total);
        $next = '';
        if ($pageindex < $pagecount) {
            $next = $this->paginationItem($q, $view, $sort, $pagesize, $pageindex + 1, $total);
        }
        $last = '';
        if ($pageindex < $pagecount - 1) {
            $last = $this->paginationItem($q, $view, $sort, $pagesize, $pagecount, $total);
        }

        return $this->renderView('Search/pagination.html.twig', ['first' => $first, 'prev' => $prev, 'current' => $current, 'next' => $next, 'last' => $last, 'count' => $total, 'ellipsisbefore' => $pageindex > 3, 'ellipsisafter' => $pageindex < $pagecount - 2]);
    }
}
