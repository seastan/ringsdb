<?php

declare(strict_types=1);

namespace App\Services;

use App\Entity\Card;
use App\Entity\CardPrinting;
use App\Entity\Comment;
use App\Entity\Decklist;
use App\Entity\Decklistslot;
use App\Entity\Sphere;
use App\Entity\User;
use App\Entity\UserCustomPackCard;
use App\Helper\StringSanitizer;
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

    public function __construct(
        private readonly EntityManagerInterface $doctrine,
        private readonly RequestStack $request_stack,
        private readonly UrlGeneratorInterface $router,
        private readonly CardRepository $cardRepository,
        private readonly SphereRepository $sphereRepository
    ) {
    }

    /**
     * The current request: the searches and the pagination read its parameters.
     */
    private function currentRequest(): Request
    {
        $request = $this->request_stack->getCurrentRequest();
        if (!$request instanceof Request) {
            throw new \LogicException('No current request.');
        }

        return $request;
    }

    public function setUser(?User $user): void
    {
        $this->user = $user;
    }

    public function setPredominantSphere(?Sphere $predominantSphere = null): void
    {
        $this->predominantSphere = $predominantSphere;
    }

    public function setLimit(int $limit): void
    {
        $this->limit = $limit;
    }

    public function setPage(int $page): void
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
        $qb->from(Decklist::class, 'd');

        // the lists display the author and the last pack of every decklist
        $qb->innerJoin('d.user', 'u');
        $qb->leftJoin('d.lastPack', 'p');
        $qb->addSelect('u', 'p');

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
        $qb->orderBy('popularity', \SortDirection::Descending);

        // tie-breaker, for a stable order and pagination
        $qb->addOrderBy('d.id', \SortDirection::Descending);

        return $this->getPaginator($qb->getQuery());
    }

    /**
     * @return Paginator<Decklist>
     */
    public function findDecklistsByAge(): Paginator
    {
        $qb = $this->getQueryBuilder();

        $qb->orderBy('d.dateCreation', \SortDirection::Descending);

        // tie-breaker, for a stable order and pagination
        $qb->addOrderBy('d.id', \SortDirection::Descending);

        return $this->getPaginator($qb->getQuery());
    }

    /**
     * @return Paginator<Decklist>
     */
    public function findDecklistsByRecentDiscussion(): Paginator
    {
        $qb = $this->getQueryBuilder();

        $qb->andWhere('d.nbComments > 0');
        $qb->orderBy('d.dateLastComment', \SortDirection::Descending);
        // tie-breaker, for a stable order and pagination
        $qb->addOrderBy('d.id', \SortDirection::Descending);

        return $this->getPaginator($qb->getQuery());
    }

    /**
     * @return Paginator<Decklist>
     */
    public function findDecklistsByFavorite(User $user): Paginator
    {
        $qb = $this->getQueryBuilder();

        $qb->innerJoin('d.favorites', 'f');
        $qb->andWhere('f = :user');
        $qb->setParameter('user', $user);
        $qb->orderBy('d.dateCreation', \SortDirection::Descending);

        // tie-breaker, for a stable order and pagination
        $qb->addOrderBy('d.id', \SortDirection::Descending);

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
        $qb->orderBy('d.dateCreation', \SortDirection::Descending);

        // tie-breaker, for a stable order and pagination
        $qb->addOrderBy('d.id', \SortDirection::Descending);

        return $this->getPaginator($qb->getQuery());
    }

    /**
     * @return Paginator<Decklist>
     */
    public function findDecklistsInHallOfFame(): Paginator
    {
        $qb = $this->getQueryBuilder();

        $qb->andWhere('d.nbVotes > 10');
        $qb->orderBy('d.nbVotes', \SortDirection::Descending);

        // tie-breaker, for a stable order and pagination
        $qb->addOrderBy('d.id', \SortDirection::Descending);

        return $this->getPaginator($qb->getQuery());
    }

    /**
     * @return Paginator<Decklist>
     */
    public function findDecklistsInHotTopic(): Paginator
    {
        $qb = $this->getQueryBuilder();

        $qb->addSelect('(SELECT count(c) FROM '.Comment::class.' c WHERE c.decklist=d AND DATE_DIFF(CURRENT_TIMESTAMP(), c.dateCreation)<1) AS HIDDEN nbRecentComments');
        $qb->orderBy('nbRecentComments', \SortDirection::Descending);
        $qb->addOrderBy('d.nbComments', \SortDirection::Descending);

        // tie-breaker, for a stable order and pagination
        $qb->addOrderBy('d.id', \SortDirection::Descending);

        return $this->getPaginator($qb->getQuery());
    }

    /**
     * @return Paginator<Decklist>
     */
    public function findDecklistsWithComplexSearch(): Paginator
    {
        $request = $this->currentRequest();

        $cards_code = $request->query->all('cards');
        $cards_to_exclude = $request->query->all('cards_to_exclude');

        $sphere_code = StringSanitizer::sanitize($request->query->get('sphere'));
        if ($sphere_code) {
            $sphere = $this->sphereRepository->findOneBy(['code' => $sphere_code]);
        }

        $numcores = $request->query->get('numcores');

        $author_name = StringSanitizer::sanitize($request->query->get('author'));

        $decklist_name = StringSanitizer::sanitize($request->query->get('name'));

        $sort = $request->query->get('sort');

        $packs = $request->query->all('packs');

        $customPackCodes = array_values(array_filter($request->query->all('custom_packs'), is_string(...)));

        $threat_op = $request->query->get('threato');
        $threat = $request->query->get('threat');

        $reputation_op = $request->query->get('reputationo');
        $reputation = $request->query->get('reputation');

        $require_description = $request->query->get('require_description');

        $qb = $this->getQueryBuilder();

        // the author, the reputation filter and the reputation sort use the user joined as u
        $filterByReputation = !empty($reputation) && is_numeric($reputation);

        if (!empty($sphere)) {
            $qb->innerJoin('d.spheres', 'w');
            $qb->andWhere('w.id = :sphere');
            $qb->setParameter('sphere', $sphere->getId());
        }

        if (!empty($author_name)) {
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

        if ($filterByReputation) {
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

        $useCustomPacks = [] !== $customPackCodes && $this->user;

        if (count($cards_code) > 0 || count($packs) > 0 || $useCustomPacks) {
            $cards = [] === $cards_code ? [] : $this->cardRepository->findBy(['code' => $cards_code]);
            foreach ($cards as $i => $card) {
                $qb->innerJoin('d.slots', "s$i");
                $qb->andWhere("s$i.card = :card$i");
                $qb->setParameter("card$i", $card);
                // Add packs containing requested cards
                // $packs[] = $card->getPack()->getId();
            }

            if (count($packs) > 0 || $useCustomPacks) {
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
                if (count($packs) > 0 && !$useCustomPacks) {
                    $packsWithCards = array_map(intval(...), $this->doctrine->getConnection()
                        ->executeQuery('SELECT DISTINCT pack_id FROM card_printing')
                        ->fetchFirstColumn());
                    $skipBuildable = 0 === count(array_diff($packsWithCards, array_map(intval(...), $packs)));
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
                    if (count($packs) > 0) {
                        $officialSubquery =
                            '(SELECT COALESCE(SUM(CASE WHEN cp.pack = 1 THEN cp.quantity * :numcores ELSE cp.quantity END), 0) '.
                            'FROM '.CardPrinting::class.' cp '.
                            'WHERE cp.card = s.card AND cp.pack IN (:packs))';
                        $qb->setParameter('packs', $packs);
                        $qb->setParameter('numcores', $cores);
                    }

                    if ($useCustomPacks) {
                        $qb->setParameter('customPackCodes', $customPackCodes);
                        $qb->setParameter('customPackUser', $this->user);
                    }

                    if (count($packs) > 0 && $useCustomPacks) {
                        // Inner version of the official subquery uses alias cp2 so it doesn't
                        // collide with cp from the outer official-check occurrence.
                        $officialSubquery2 = str_replace(
                            ['FROM '.CardPrinting::class.' cp ', 'cp.pack', 'cp.card', 'cp.quantity'],
                            ['FROM '.CardPrinting::class.' cp2 ', 'cp2.pack', 'cp2.card', 'cp2.quantity'],
                            $officialSubquery
                        );

                        // Slot uncovered when official alone fails AND no custom entry covers the gap.
                        // "Custom covers the gap" ⟺ remaining need after custom ≤ official_copies.
                        // remaining = CASE WHEN s.quantity >= ucpc.quantity THEN s.quantity - ucpc.quantity ELSE 0 END
                        // (the CASE avoids unsigned-integer subtraction underflow when custom has surplus copies).
                        $uncoveredCondition =
                            's.quantity > '.$officialSubquery.
                            ' AND NOT EXISTS ('.
                                'SELECT ucpc.id FROM '.UserCustomPackCard::class.' ucpc '.
                                'JOIN ucpc.customPack ucp '.
                                'WHERE ucpc.card = s.card '.
                                'AND ucp.code IN (:customPackCodes) '.
                                'AND ucp.user = :customPackUser '.
                                'AND CASE WHEN s.quantity >= ucpc.quantity THEN s.quantity - ucpc.quantity ELSE 0 END <= '.$officialSubquery2.
                            ')';
                    } elseif (count($packs) > 0) {
                        $uncoveredCondition = 's.quantity > '.$officialSubquery;
                    } else {
                        // Custom-only: custom pack must fully supply the slot on its own.
                        $uncoveredCondition =
                            'NOT EXISTS ('.
                                'SELECT ucpc.id FROM '.UserCustomPackCard::class.' ucpc '.
                                'JOIN ucpc.customPack ucp '.
                                'WHERE ucpc.card = s.card '.
                                'AND ucp.code IN (:customPackCodes) '.
                                'AND ucp.user = :customPackUser '.
                                'AND ucpc.quantity >= s.quantity'.
                            ')';
                    }

                    $qb->andWhere(
                        'NOT EXISTS ('.
                            'SELECT s.id FROM '.Decklistslot::class.' s '.
                            'WHERE s.decklist = d AND '.$uncoveredCondition.
                        ')'
                    );
                }
            }

            if (count($cards_to_exclude) > 0) {
                $sub = $this->doctrine->createQueryBuilder();
                $sub->select('k');
                $sub->from(Card::class, 'k');
                $sub->innerJoin(Decklistslot::class, 't', Query\Expr\Join::ON, 't.card = k');
                $sub->where('t.decklist = d');
                $sub->andWhere($sub->expr()->in('k.code', $cards_to_exclude));
                $qb->andWhere($qb->expr()->not($qb->expr()->exists($sub->getDQL())));
            }

            // (the former Core-only "num cores" quantity check is now subsumed by the
            //  quantity-aware allowed-packs filter above.)
        }

        switch ($sort) {
            case 'date':
                $qb->orderBy('d.dateCreation', \SortDirection::Descending);
                break;

            case 'likes':
                $qb->orderBy('d.nbVotes', \SortDirection::Descending);
                break;

            case 'threat':
                $qb->orderBy('d.startingThreat', \SortDirection::Ascending);
                break;

            case 'reputation':
                // with DISTINCT, MySQL 5.7+ only sorts on selected columns
                $qb->addSelect('u.reputation AS HIDDEN reputation');
                $qb->orderBy('reputation', \SortDirection::Descending);
                break;

            case 'popularity':
            default:
                $qb->addSelect('(1+d.nbVotes)/(1+POWER(DATE_DIFF(CURRENT_TIMESTAMP(), d.dateCreation), 2)) AS HIDDEN popularity');
                $qb->orderBy('popularity', \SortDirection::Descending);
                break;
        }

        // tie-breaker, for a stable order and pagination
        $qb->addOrderBy('d.id', \SortDirection::Descending);

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
        $route = $request->attributes->get('_route');
        $route_params = $request->attributes->all('_route_params');
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
        $route = $request->attributes->get('_route');
        $route_params = $request->attributes->all('_route_params');

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
        $route = $request->attributes->get('_route');
        $route_params = $request->attributes->all('_route_params');

        $query = $request->query->all();
        $params = $query + $route_params;

        $next_page = min($this->getNumberOfPages(), $this->page + 1);
        $params['page'] = $next_page;

        return $this->router->generate($route, $params);
    }
}
