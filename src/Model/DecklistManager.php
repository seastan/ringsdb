<?php

declare(strict_types=1);

namespace App\Model;

use App\Entity\Card;
use App\Entity\Decklist;
use App\Entity\Sphere;
use App\Entity\User;
use App\Repository\CardRepository;
use App\Repository\SphereRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * The job of this class is to find and return decklists.
 *
 * @author alsciende
 *
 * @property int $maxcount Number of found rows for last request
 */
class DecklistManager
{
    /**
     * @var Sphere|null
     */
    protected $predominantSphere;
    /**
     * @var int
     */
    protected $page = 1;
    /**
     * @var int
     */
    protected $start = 0;
    /**
     * @var int
     */
    protected $limit = 30;
    /**
     * @var int
     */
    protected $maxcount = 0;
    /**
     * @var User|null
     */
    protected $user;

    /**
     * @var EntityManagerInterface
     */
    private $doctrine;

    /**
     * @var RequestStack
     */
    private $request_stack;

    /**
     * @var UrlGeneratorInterface
     */
    private $router;

    /**
     * @var CardRepository
     */
    private $cardRepository;

    /**
     * @var SphereRepository
     */
    private $sphereRepository;

    public function __construct(EntityManagerInterface $doctrine, RequestStack $request_stack, UrlGeneratorInterface $router, CardRepository $cardRepository, SphereRepository $sphereRepository)
    {
        $this->doctrine = $doctrine;
        $this->request_stack = $request_stack;
        $this->router = $router;
        $this->cardRepository = $cardRepository;
        $this->sphereRepository = $sphereRepository;
    }

    /**
     * The current request: the searches and the pagination read its parameters.
     */
    private function currentRequest(): Request
    {
        $request = $this->request_stack->getCurrentRequest();
        if (null === $request) {
            throw new \LogicException('No current request.');
        }

        return $request;
    }

    public function setUser($user): void
    {
        $this->user = $user;
    }

    public function setPredominantSphere(?Sphere $predominantSphere = null): void
    {
        $this->predominantSphere = $predominantSphere;
    }

    public function setLimit($limit): void
    {
        $this->limit = $limit;
    }

    public function setPage($page): void
    {
        $this->page = max($page, 1);
        $this->start = ($this->page - 1) * $this->limit;
    }

    public function getMaxCount(): int
    {
        return $this->maxcount;
    }

    /**
     * creates the basic query builder and initializes it.
     */
    private function getQueryBuilder(): QueryBuilder
    {
        $qb = $this->doctrine->createQueryBuilder();
        $qb->select('d');
        $qb->from('App:Decklist', 'd');

        if ($this->predominantSphere) {
            $qb->where('d.predominantSphere = :predominantSphere');
            $qb->setParameter('predominantSphere', $this->predominantSphere);
        }
        $qb->setFirstResult($this->start);
        $qb->setMaxResults($this->limit);
        $qb->distinct();

        return $qb;
    }

    /**
     * @param Query<mixed, Decklist> $query
     *
     * @return Paginator<Decklist>
     */
    private function getPaginator(Query $query): Paginator
    {
        $paginator = new Paginator($query, $fetchJoinCollection = false);
        $this->maxcount = $paginator->count();

        return $paginator;
    }

    /**
     * @return ArrayCollection<int, Decklist>
     */
    public function getEmptyList(): ArrayCollection
    {
        $this->maxcount = 0;

        return new ArrayCollection([]);
    }

    /**
     * @return Paginator<Decklist>
     */
    public function findDecklistsByPopularity(): Paginator
    {
        $qb = $this->getQueryBuilder();
        $qb->addSelect('(1+d.nbVotes)/(1+POWER(DATE_DIFF(CURRENT_TIMESTAMP(), d.dateCreation), 2)) AS HIDDEN popularity');
        $qb->orderBy('popularity', 'DESC');

        // tie-breaker, for a stable order and pagination
        $qb->addOrderBy('d.id', 'DESC');

        return $this->getPaginator($qb->getQuery());
    }

    /**
     * @return Paginator<Decklist>
     */
    public function findDecklistsByAge(): Paginator
    {
        $qb = $this->getQueryBuilder();

        $qb->orderBy('d.dateCreation', 'DESC');

        // tie-breaker, for a stable order and pagination
        $qb->addOrderBy('d.id', 'DESC');

        return $this->getPaginator($qb->getQuery());
    }

    /**
     * @return Paginator<Decklist>
     */
    public function findDecklistsByRecentDiscussion(): Paginator
    {
        $qb = $this->getQueryBuilder();

        $qb->andWhere('d.nbComments > 0');
        $qb->orderBy('d.dateLastComment', 'DESC');
        // tie-breaker, for a stable order and pagination
        $qb->addOrderBy('d.id', 'DESC');

        return $this->getPaginator($qb->getQuery());
    }

    /**
     * @return Paginator<Decklist>
     */
    public function findDecklistsByFavorite(User $user): Paginator
    {
        $qb = $this->getQueryBuilder();

        $qb->leftJoin('d.favorites', 'u');
        $qb->andWhere('u = :user');
        $qb->setParameter('user', $user);
        $qb->orderBy('d.dateCreation', 'DESC');

        // tie-breaker, for a stable order and pagination
        $qb->addOrderBy('d.id', 'DESC');

        return $this->getPaginator($qb->getQuery());
    }

    /**
     * @return Paginator<Decklist>
     */
    public function findDecklistsByAuthor(User $user): Paginator
    {
        $qb = $this->getQueryBuilder();

        $qb->andWhere('d.user = :user');
        $qb->setParameter('user', $user);
        $qb->orderBy('d.dateCreation', 'DESC');

        // tie-breaker, for a stable order and pagination
        $qb->addOrderBy('d.id', 'DESC');

        return $this->getPaginator($qb->getQuery());
    }

    /**
     * @return Paginator<Decklist>
     */
    public function findDecklistsInHallOfFame(): Paginator
    {
        $qb = $this->getQueryBuilder();

        $qb->andWhere('d.nbVotes > 10');
        $qb->orderBy('d.nbVotes', 'DESC');

        // tie-breaker, for a stable order and pagination
        $qb->addOrderBy('d.id', 'DESC');

        return $this->getPaginator($qb->getQuery());
    }

    /**
     * @return Paginator<Decklist>
     */
    public function findDecklistsInHotTopic(): Paginator
    {
        $qb = $this->getQueryBuilder();

        $qb->addSelect('(SELECT count(c) FROM App:Comment c WHERE c.decklist=d AND DATE_DIFF(CURRENT_TIMESTAMP(), c.dateCreation)<1) AS HIDDEN nbRecentComments');
        $qb->orderBy('nbRecentComments', 'DESC');
        $qb->addOrderBy('d.nbComments', 'DESC');

        // tie-breaker, for a stable order and pagination
        $qb->addOrderBy('d.id', 'DESC');

        return $this->getPaginator($qb->getQuery());
    }

    /**
     * @return Paginator<Decklist>
     */
    public function findDecklistsWithComplexSearch(): Paginator
    {
        $request = $this->currentRequest();

        $cards_code = $request->query->get('cards');
        if (!is_array($cards_code)) {
            $cards_code = [];
        }
        $cards_to_exclude = $request->query->get('cards_to_exclude');
        if (!is_array($cards_to_exclude)) {
            $cards_to_exclude = [];
        }

        $sphere_code = filter_var($request->query->get('sphere'), FILTER_SANITIZE_STRING);
        if ($sphere_code) {
            $sphere = $this->sphereRepository->findOneBy(['code' => $sphere_code]);
        }

        $numcores = $request->query->get('numcores');

        $author_name = filter_var($request->query->get('author'), FILTER_SANITIZE_STRING);

        $decklist_name = filter_var($request->query->get('name'), FILTER_SANITIZE_STRING);

        $sort = $request->query->get('sort');

        $packs = $request->query->get('packs');
        if (!is_array($packs)) {
            $packs = [];
        }

        $customPackCodes = array_values(array_filter((array) $request->query->get('custom_packs', []), 'is_string'));

        $threat_op = $request->query->get('threato');
        $threat = $request->query->get('threat');

        $reputation_op = $request->query->get('reputationo');
        $reputation = $request->query->get('reputation');

        $require_description = $request->query->get('require_description');

        $qb = $this->getQueryBuilder();
        $joinTables = [];

        if (!empty($sphere)) {
            $qb->innerJoin('d.spheres', 'w');
            $qb->andWhere('w.id = :sphere');
            $qb->setParameter('sphere', $sphere->getId());
        }

        if (!empty($author_name)) {
            $qb->innerJoin('d.user', 'u');
            $joinTables[] = 'd.user';
            $qb->andWhere('u.username = :username');
            $qb->setParameter('username', $author_name);
        }

        if (!empty($decklist_name)) {
            $qb->andWhere('d.name like :deckname');
            $qb->setParameter('deckname', "%$decklist_name%");
        }

        if (!empty($threat) && is_numeric($threat)) {
            if ('>' == $threat_op) {
                $qb->andWhere('d.startingThreat > :threat');
            } elseif ('<' == $threat_op) {
                $qb->andWhere('d.startingThreat < :threat');
            } else {
                $qb->andWhere('d.startingThreat = :threat');
            }
            $qb->setParameter('threat', $threat);
        }

        if (!empty($reputation) && is_numeric($reputation)) {
            $qb->innerJoin('d.user', 'u');
            if ('>' == $reputation_op) {
                $qb->andWhere('u.reputation > :reputation');
            } elseif ('<' == $reputation_op) {
                $qb->andWhere('u.reputation < :reputation');
            } else {
                $qb->andWhere('u.reputation = :reputation');
            }
            $qb->setParameter('reputation', $reputation);
        }

        if ($require_description) {
            $qb->andWhere($qb->expr()->gt($qb->expr()->length('d.descriptionHtml'), 0));
        }

        $useCustomPacks = !empty($customPackCodes) && $this->user;

        if (!empty($cards_code) || !empty($packs) || $useCustomPacks) {
            if (!empty($cards_code)) {
                foreach ($cards_code as $i => $card_code) {
                    /* @var $card \App\Entity\Card */
                    $card = $this->cardRepository->findOneBy(['code' => $card_code]);
                    if (!$card) {
                        continue;
                    }
                    $qb->innerJoin('d.slots', "s$i");
                    $qb->andWhere("s$i.card = :card$i");
                    $qb->setParameter("card$i", $card);
                    // Add packs containing requested cards
                    // $packs[] = $card->getPack()->getId();
                }
            }
            if (!empty($packs) || $useCustomPacks) {
                // A decklist matches iff every slot's card can be supplied in sufficient
                // quantity by the allowed official packs OR by the user's custom packs.
                $cores = max(1, (int) $numcores);

                // --- Short-circuit the pathological "owns everything" case (incident 2026-09-24) ---
                // If the selected official packs cover every pack that actually contains cards,
                // availability is maximal for every card, so the buildable NOT EXISTS below can only
                // ever exclude decks that are unbuildable under ANY collection. Skipping it in that
                // case avoids the full O(decklists x slots) scan that saturated php-fpm when crawlers
                // submit every pack checkbox (the giant ?packs[]=... URLs).
                // This holds for any core count: with every pack selected, the repackaged products
                // also supply the Core Set cards, so numcores changes nothing (on the 2026-06 prod
                // data the same 2 decklists are excluded at 1, 2 and 3 cores). Running the full check
                // for numcores < 3 would cost ~10x for no difference in results.
                // Accepted deviation: in this all-packs case, the few decks that use more copies of a
                // card than exist in total (unbuildable with any collection anyway) are no longer
                // filtered out — this keeps the short-circuit table-free with no per-request scan.
                $skipBuildable = false;
                if (!empty($packs) && !$useCustomPacks) {
                    $packsWithCards = array_map('intval', $this->doctrine->getConnection()
                        ->executeQuery('SELECT DISTINCT pack_id FROM card_printing')
                        ->fetchAll(\PDO::FETCH_COLUMN));
                    $skipBuildable = 0 === count(array_diff($packsWithCards, array_map('intval', $packs)));
                }

                if (!$skipBuildable) {
                    // Build the "slot is uncovered" condition.
                    //
                    // Official-only: slot.quantity > official_copies  (original form)
                    //
                    // Combined: official alone doesn't cover AND no single custom-pack entry
                    //   together with official covers the remaining need.
                    //   "Custom entry covers remaining" ⟺ s.quantity - ucpc.quantity <= official_copies
                    //   i.e. ucpc.quantity + official_copies >= s.quantity.
                    //   We put the arithmetic on the left side of ≤ so the right side stays a
                    //   plain scalar subquery — the form Doctrine's DQL parser handles cleanly.
                    //
                    // Custom-only: same nested NOT EXISTS but with official_copies = 0, expressed
                    //   as ucpc.quantity >= s.quantity (left-side arithmetic becomes s.quantity - ucpc.quantity <= 0).
                    $officialSubquery = '';
                    if (!empty($packs)) {
                        $officialSubquery =
                            '(SELECT COALESCE(SUM(CASE WHEN cp.pack = 1 THEN cp.quantity * :numcores ELSE cp.quantity END), 0) '.
                            'FROM App:CardPrinting cp '.
                            'WHERE cp.card = s.card AND cp.pack IN (:packs))';
                        $qb->setParameter('packs', $packs);
                        $qb->setParameter('numcores', $cores);
                    }

                    if ($useCustomPacks) {
                        $qb->setParameter('customPackCodes', $customPackCodes);
                        $qb->setParameter('customPackUser', $this->user);
                    }

                    if (!empty($packs) && $useCustomPacks) {
                        // Inner version of the official subquery uses alias cp2 so it doesn't
                        // collide with cp from the outer official-check occurrence.
                        $officialSubquery2 = str_replace(
                            ['FROM App:CardPrinting cp ', 'cp.pack', 'cp.card', 'cp.quantity'],
                            ['FROM App:CardPrinting cp2 ', 'cp2.pack', 'cp2.card', 'cp2.quantity'],
                            $officialSubquery
                        );

                        // Slot uncovered when official alone fails AND no custom entry covers the gap.
                        // "Custom covers the gap" ⟺ remaining need after custom ≤ official_copies.
                        // remaining = CASE WHEN s.quantity >= ucpc.quantity THEN s.quantity - ucpc.quantity ELSE 0 END
                        // (the CASE avoids unsigned-integer subtraction underflow when custom has surplus copies).
                        $uncoveredCondition =
                            's.quantity > '.$officialSubquery.
                            ' AND NOT EXISTS ('.
                                'SELECT ucpc.id FROM App:UserCustomPackCard ucpc '.
                                'JOIN ucpc.customPack ucp '.
                                'WHERE ucpc.card = s.card '.
                                'AND ucp.code IN (:customPackCodes) '.
                                'AND ucp.user = :customPackUser '.
                                'AND CASE WHEN s.quantity >= ucpc.quantity THEN s.quantity - ucpc.quantity ELSE 0 END <= '.$officialSubquery2.
                            ')';
                    } elseif (!empty($packs)) {
                        $uncoveredCondition = 's.quantity > '.$officialSubquery;
                    } else {
                        // Custom-only: custom pack must fully supply the slot on its own.
                        $uncoveredCondition =
                            'NOT EXISTS ('.
                                'SELECT ucpc.id FROM App:UserCustomPackCard ucpc '.
                                'JOIN ucpc.customPack ucp '.
                                'WHERE ucpc.card = s.card '.
                                'AND ucp.code IN (:customPackCodes) '.
                                'AND ucp.user = :customPackUser '.
                                'AND ucpc.quantity >= s.quantity'.
                            ')';
                    }

                    $qb->andWhere(
                        'NOT EXISTS ('.
                            'SELECT s.id FROM App:Decklistslot s '.
                            'WHERE s.decklist = d AND '.$uncoveredCondition.
                        ')'
                    );
                }
            }
            if (!empty($cards_to_exclude)) {
                $sub = $this->doctrine->createQueryBuilder();
                $sub->select('k');
                $sub->from('App:Card', 'k');
                $sub->innerJoin('App:Decklistslot', 't', 'WITH', 't.card = k');
                $sub->where('t.decklist = d');
                $sub->andWhere($sub->expr()->in('k.code', $cards_to_exclude));
                $qb->andWhere($qb->expr()->not($qb->expr()->exists($sub->getDQL())));
            }
            // (the former Core-only "num cores" quantity check is now subsumed by the
            //  quantity-aware allowed-packs filter above.)
        }

        switch ($sort) {
            case 'date':
                $qb->orderBy('d.dateCreation', 'DESC');
                break;

            case 'likes':
                $qb->orderBy('d.nbVotes', 'DESC');
                break;

            case 'threat':
                $qb->orderBy('d.startingThreat', 'ASC');
                break;

            case 'reputation':
                if (!in_array('d.user', $joinTables)) {
                    $qb->innerJoin('d.user', 'u');
                }
                // with DISTINCT, MySQL 5.7+ only sorts on selected columns
                $qb->addSelect('u.reputation AS HIDDEN reputation');
                $qb->orderBy('reputation', 'DESC');
                break;

            case 'popularity':
            default:
                $qb->addSelect('(1+d.nbVotes)/(1+POWER(DATE_DIFF(CURRENT_TIMESTAMP(), d.dateCreation), 2)) AS HIDDEN popularity');
                $qb->orderBy('popularity', 'DESC');
                break;
        }

        // tie-breaker, for a stable order and pagination
        $qb->addOrderBy('d.id', 'DESC');

        return $this->getPaginator($qb->getQuery());
    }

    public function getNumberOfPages(): int
    {
        return intval(ceil($this->maxcount / $this->limit));
    }

    /**
     * @return list<array{numero: int, url: string, current: bool}>
     */
    public function getAllPages(): array
    {
        $request = $this->currentRequest();
        $route = $request->get('_route');
        $route_params = $request->get('_route_params');
        $query = $request->query->all();

        $params = $query + $route_params;

        $number_of_pages = $this->getNumberOfPages();
        $pages = [];
        for ($page = 1; $page <= $number_of_pages; ++$page) {
            $pages[] = [
                'numero' => $page,
                'url' => $this->router->generate($route, ['page' => $page] + $params),
                'current' => $page == $this->page,
            ];
        }

        return $pages;
    }

    /**
     * @return array<int, mixed>
     */
    public function getClosePages(): array
    {
        $allPages = $this->getAllPages();
        $numero_courant = $this->page - 1;
        $pages = [];
        foreach ($allPages as $numero => $page) {
            if (0 === $numero || $numero === count($allPages) - 1 || abs($numero - $numero_courant) <= 2) {
                $pages[] = $page;
            }
        }

        return $pages;
    }

    public function getPreviousUrl(): ?string
    {
        if (1 === $this->page) {
            return null;
        }

        $request = $this->currentRequest();
        $route = $request->get('_route');
        $route_params = $request->get('_route_params');

        $query = $request->query->all();
        $params = $query + $route_params;

        $previous_page = max(1, $this->page - 1);
        $params['page'] = $previous_page;

        return $this->router->generate($route, $params);
    }

    public function getNextUrl(): ?string
    {
        if ($this->page === $this->getNumberOfPages()) {
            return null;
        }

        $request = $this->currentRequest();
        $route = $request->get('_route');
        $route_params = $request->get('_route_params');

        $query = $request->query->all();
        $params = $query + $route_params;

        $next_page = min($this->getNumberOfPages(), $this->page + 1);
        $params['page'] = $next_page;

        return $this->router->generate($route, $params);
    }
}
