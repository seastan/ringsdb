<?php

declare(strict_types=1);

namespace App\Controller\Fellowship;

use App\Entity\Cycle;
use App\Model\FellowshipManager;
use App\Repository\CycleRepository;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ListFellowshipController extends AbstractController
{
    private int $cacheExpiration;
    private FellowshipManager $fellowshipManager;
    private CycleRepository $cycleRepository;

    public function __construct(
        int $cacheExpiration,
        FellowshipManager $fellowshipManager,
        CycleRepository $cycleRepository
    ) {
        $this->cacheExpiration = $cacheExpiration;
        $this->fellowshipManager = $fellowshipManager;
        $this->cycleRepository = $cycleRepository;
    }

    /**
     * @param int $page
     *
     * @Route(
     *     "/fellowships/{type}/{page}",
     *     name="fellowships_list",
     *     methods={"GET"},
     *     requirements={"page"="\d+"},
     *     defaults={"type"="popular", "page"=1}
     * )
     */
    public function listAction($type, $page = 1, Request $request): Response
    {
        $response = new Response();
        $response->setPublic();
        $response->setMaxAge($this->cacheExpiration);
        $this->fellowshipManager->setLimit(30);
        $this->fellowshipManager->setPage($page);
        $header = '';
        switch ($type) {
            case 'find':
                $pagetitle = 'Fellowship search results';
                $header = $this->searchForm($request);
                $this->fellowshipManager->setUser($this->getUser());
                $paginator = $this->fellowshipManager->findFellowshipsWithComplexSearch();
                break;
            case 'favorites':
                $response->setPrivate();
                $user = $this->getUser();
                if ($user) {
                    $paginator = $this->fellowshipManager->findFellowshipsByFavorite($user);
                } else {
                    $paginator = $this->fellowshipManager->getEmptyList();
                }
                $pagetitle = 'Favorite Fellowships';
                break;
            case 'mine':
                $response->setPrivate();
                $user = $this->getUser();
                if ($user) {
                    $paginator = $this->fellowshipManager->findFellowshipsByAuthor($user);
                } else {
                    $paginator = $this->fellowshipManager->getEmptyList();
                }
                $pagetitle = 'My Public Fellowships';
                break;
            case 'recent':
                $paginator = $this->fellowshipManager->findFellowshipsByAge();
                $pagetitle = 'Recent Fellowships';
                break;
            case 'halloffame':
                $paginator = $this->fellowshipManager->findFellowshipsInHallOfFame();
                $pagetitle = 'Hall of Fame';
                break;
            case 'hottopics':
                $paginator = $this->fellowshipManager->findFellowshipsInHotTopic();
                $pagetitle = 'Hot Topics';
                break;
            case 'popular':
            default:
                $paginator = $this->fellowshipManager->findFellowshipsByPopularity();
                $pagetitle = 'Popular Fellowships';
                break;
        }

        return $this->render('Fellowship/public-fellowships.html.twig', ['pagetitle' => $pagetitle, 'pagedescription' => 'Browse the collection of thousands of premade decks.', 'fellowships' => $paginator, 'url' => $request->getRequestUri(), 'header' => $header, 'type' => $type, 'pages' => $this->fellowshipManager->getClosePages(), 'prevurl' => $this->fellowshipManager->getPreviousUrl(), 'nexturl' => $this->fellowshipManager->getNextUrl()], $response);
    }

    private function searchForm(Request $request): string
    {
        $dbh = $this->getDoctrine()->getConnection();
        $cards_code = $request->query->get('cards');
        $author_name = filter_var($request->query->get('author'), FILTER_SANITIZE_STRING);
        $fellowship_name = filter_var($request->query->get('name'), FILTER_SANITIZE_STRING);
        $nb_decks = intval(filter_var($request->query->get('nb_decks'), FILTER_SANITIZE_NUMBER_INT));
        $numcores = $request->query->get('numcores');
        $numplaysets = $request->query->get('numplaysets');
        $sort = $request->query->get('sort');
        $packs = $request->query->get('packs');
        if (!is_array($packs)) {
            $packs = $dbh->executeQuery('SELECT id FROM pack')->fetchAll(\PDO::FETCH_COLUMN);
        }
        $categories = [];
        $on = 0;
        $off = 0;
        $categories[] = ['label' => 'Core / Deluxe', 'packs' => []];
        $list_cycles = $this->cycleRepository->findBy([], ['position' => 'ASC']);
        foreach ($list_cycles as $cycle) {
            /* @var $cycle Cycle */
            $size = count($cycle->getPacks());
            $first_pack = $cycle->getPacks()->first();
            if (0 == $cycle->getPosition() || false === $first_pack) {
                continue;
            }
            if (1 === $size && $first_pack->getName() == $cycle->getName()) {
                $checked = count($packs) ? in_array($first_pack->getId(), $packs) : true;
                if ($checked) {
                    ++$on;
                } else {
                    ++$off;
                }
                $categories[0]['packs'][] = ['id' => $first_pack->getId(), 'label' => $first_pack->getName(), 'checked' => $checked, 'future' => null === $first_pack->getDateRelease()];
            } else {
                $category = ['label' => $cycle->getName(), 'packs' => []];
                foreach ($cycle->getPacks() as $pack) {
                    $checked = count($packs) ? in_array($pack->getId(), $packs) : true;
                    if ($checked) {
                        ++$on;
                    } else {
                        ++$off;
                    }
                    $category['packs'][] = ['id' => $pack->getId(), 'label' => $pack->getName(), 'checked' => $checked, 'future' => null === $pack->getDateRelease()];
                }
                $categories[] = $category;
            }
        }
        $params = ['allowed' => $categories, 'on' => $on, 'off' => $off, 'author' => $author_name, 'name' => $fellowship_name, 'numcores' => $numcores, 'numplaysets' => $numplaysets];
        $params['sort_'.$sort] = ' selected="selected"';
        $params['nb_decks_selected'] = $nb_decks;
        if (!empty($cards_code) && is_array($cards_code)) {
            $cards = $dbh->executeQuery("SELECT\n    \t\t\t\tc.name,\n    \t\t\t\tc.code,\n                    s.code AS sphere_code,\n                    t.name AS type_name\n    \t\t\t\tFROM card c\n                    INNER JOIN sphere s ON s.id = c.sphere_id\n                    INNER JOIN type t ON t.id = c.type_id\n                    INNER JOIN card_printing cpr ON cpr.id = (SELECT cp2.id FROM card_printing cp2 WHERE cp2.card_id = c.id ORDER BY cp2.position ASC, cp2.id ASC LIMIT 1)\n                    INNER JOIN pack p ON p.id = cpr.pack_id\n                    WHERE c.code IN (?)\n    \t\t\t\tORDER BY c.code DESC", [$cards_code], [Connection::PARAM_INT_ARRAY])->fetchAll();
            $params['cards'] = '';
            foreach ($cards as $card) {
                $params['cards'] .= $this->renderView('Search/card.html.twig', $card);
            }
        }

        return $this->renderView('Fellowship/form.html.twig', $params);
    }
}
