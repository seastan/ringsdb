<?php

declare(strict_types=1);

namespace App\Controller\Decklist;

use App\Entity\Cycle;
use App\Model\DecklistManager;
use App\Repository\CycleRepository;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ListDecklistController extends AbstractController
{
    private DecklistManager $decklistManager;
    private int $cacheExpiration;
    private CycleRepository $cycleRepository;

    public function __construct(
        int $cacheExpiration,
        DecklistManager $decklistManager,
        CycleRepository $cycleRepository
    ) {
        $this->decklistManager = $decklistManager;
        $this->cacheExpiration = $cacheExpiration;
        $this->cycleRepository = $cycleRepository;
    }

    /**
     * displays the lists of decklists.
     *
     * @param int $page
     *
     * @Route(
     *     "/decklists/{type}/{page}",
     *     name="decklists_list",
     *     methods={"GET"},
     *     requirements={"page"="\d+"},
     *     defaults={"type"="popular", "page"=1}
     * )
     */
    public function __invoke(Request $request, $type, $page = 1): Response
    {
        $response = new Response();
        $response->setPublic();
        $response->setMaxAge($this->cacheExpiration);
        $this->decklistManager->setLimit(30);
        $this->decklistManager->setPage($page);
        $header = '';
        switch ($type) {
            case 'find':
                $pagetitle = 'Decklist search results';
                $header = $this->searchForm($request);
                $this->decklistManager->setUser($this->getUser());
                $paginator = $this->decklistManager->findDecklistsWithComplexSearch();
                break;
            case 'favorites':
                $response->setPrivate();
                $user = $this->getUser();
                if ($user) {
                    $paginator = $this->decklistManager->findDecklistsByFavorite($user);
                } else {
                    $paginator = $this->decklistManager->getEmptyList();
                }
                $pagetitle = 'Favorite Decklists';
                break;
            case 'mine':
                $response->setPrivate();
                $user = $this->getUser();
                if ($user) {
                    $paginator = $this->decklistManager->findDecklistsByAuthor($user);
                } else {
                    $paginator = $this->decklistManager->getEmptyList();
                }
                $pagetitle = 'My Decklists';
                break;
            case 'recent':
                $paginator = $this->decklistManager->findDecklistsByAge();
                $pagetitle = 'Recent Decklists';
                break;
            case 'halloffame':
                $paginator = $this->decklistManager->findDecklistsInHallOfFame();
                $pagetitle = 'Hall of Fame';
                break;
            case 'hottopics':
                $paginator = $this->decklistManager->findDecklistsInHotTopic();
                $pagetitle = 'Hot Topics';
                break;
            case 'popular':
            default:
                $paginator = $this->decklistManager->findDecklistsByPopularity();
                $pagetitle = 'Popular Decklists';
                break;
        }

        return $this->render('Decklist/decklists.html.twig', ['pagetitle' => $pagetitle, 'pagedescription' => 'Browse the collection of thousands of premade decks.', 'decklists' => $paginator, 'url' => $request->getRequestUri(), 'header' => $header, 'type' => $type, 'pages' => $this->decklistManager->getClosePages(), 'prevurl' => $this->decklistManager->getPreviousUrl(), 'nexturl' => $this->decklistManager->getNextUrl()], $response);
    }

    private function searchForm(Request $request): string
    {
        $dbh = $this->getDoctrine()->getConnection();
        $cards_code = $request->query->get('cards');
        $cards_to_exclude = $request->query->get('cards_to_exclude');
        $sphere_code = filter_var($request->query->get('sphere'), FILTER_SANITIZE_STRING);
        $author_name = filter_var($request->query->get('author'), FILTER_SANITIZE_STRING);
        $decklist_name = filter_var($request->query->get('name'), FILTER_SANITIZE_STRING);
        $starting_threat = intval(filter_var($request->query->get('threat'), FILTER_SANITIZE_NUMBER_INT));
        $starting_threat_o = $request->query->get('threato');
        $author_reputation = intval(filter_var($request->query->get('reputation'), FILTER_SANITIZE_NUMBER_INT));
        $author_reputation_o = $request->query->get('reputationo');
        $numcores = $request->query->get('numcores');
        $require_description = $request->query->get('require_description');
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
        $params = ['allowed' => $categories, 'on' => $on, 'off' => $off, 'author' => $author_name, 'name' => $decklist_name, 'threat' => $starting_threat, 'threato' => $starting_threat_o, 'reputation' => $author_reputation, 'reputationo' => $author_reputation_o, 'numcores' => $numcores, 'require_description' => $require_description];
        $params['sort_'.$sort] = ' selected="selected"';
        $params['spheres'] = $dbh->executeQuery("SELECT\n                s.name,\n                s.code\n                FROM sphere s\n                ORDER BY s.name ASC")->fetchAll();
        $params['sphere_selected'] = $sphere_code;
        if (!empty($cards_code) && is_array($cards_code)) {
            $cards = $dbh->executeQuery("SELECT\n    \t\t\t\tc.name,\n    \t\t\t\tc.code,\n                    s.code AS sphere_code,\n                    t.name AS type_name\n    \t\t\t\tFROM card c\n                    INNER JOIN sphere s ON s.id = c.sphere_id\n                    INNER JOIN type t ON t.id = c.type_id\n                    INNER JOIN card_printing cpr ON cpr.id = (SELECT cp2.id FROM card_printing cp2 JOIN pack pp ON pp.id = cp2.pack_id WHERE cp2.card_id = c.id ORDER BY (pp.date_release IS NULL), pp.date_release, cp2.position, cp2.id LIMIT 1)\n                    INNER JOIN pack p ON p.id = cpr.pack_id\n                    WHERE c.code IN (?)\n    \t\t\t\tORDER BY c.code DESC", [$cards_code], [Connection::PARAM_INT_ARRAY])->fetchAll();
            $params['cards'] = '';
            foreach ($cards as $card) {
                $params['cards'] .= $this->renderView('Search/card.html.twig', $card);
            }
        }
        if (!empty($cards_to_exclude) && is_array($cards_to_exclude)) {
            $cards_to_exclude = $dbh->executeQuery("SELECT\n    \t\t\t\tk.name,\n    \t\t\t\tk.code,\n                    s.code AS sphere_code,\n                    t.name AS type_name\n    \t\t\t\tFROM card k\n                    INNER JOIN sphere s ON s.id = k.sphere_id\n                    INNER JOIN type t ON t.id = k.type_id\n                    INNER JOIN card_printing kpr ON kpr.id = (SELECT cp2.id FROM card_printing cp2 JOIN pack pp ON pp.id = cp2.pack_id WHERE cp2.card_id = k.id ORDER BY (pp.date_release IS NULL), pp.date_release, cp2.position, cp2.id LIMIT 1)\n                    INNER JOIN pack p ON p.id = kpr.pack_id\n                    WHERE k.code IN (?)\n    \t\t\t\tORDER BY k.code DESC", [$cards_to_exclude], [Connection::PARAM_INT_ARRAY])->fetchAll();
            $params['cards_to_exclude'] = '';
            foreach ($cards_to_exclude as $card_to_exclude) {
                $params['cards_to_exclude'] .= $this->renderView('Search/card-to-exclude.html.twig', $card_to_exclude);
            }
        }

        return $this->renderView('Search/form.html.twig', $params);
    }
}
