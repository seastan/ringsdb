<?php

declare(strict_types=1);

namespace App\Model;

use App\Entity\Card;
use App\Entity\CardPrinting;
use App\Entity\Decklistslot;
use App\Entity\Fellowship;
use App\Entity\FellowshipComment;
use App\Entity\FellowshipDecklist;
use App\Entity\User;
use App\Entity\UserCustomPackCard;
use App\Helper\StringSanitizer;
use App\Repository\CardRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * The job of this class is to find and return fellowships.
 *
 * @author alsciende
 *
 * @property int $maxcount Number of found rows for last request
 */
class FellowshipManager
{
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
        private readonly CardRepository $cardRepository
    ) {
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

    public function setUser(?User $user): void
    {
        $this->user = $user;
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
        $qb->from(Fellowship::class, 'd');
        $qb->andWhere('d.isPublic = 1');
        $qb->setFirstResult($this->start);
        $qb->setMaxResults($this->limit);
        $qb->distinct();

        return $qb;
    }

    /**
     * creates the paginator around the query.
     *
     * @param Query<mixed, Fellowship> $query
     *
     * @return Paginator<Fellowship>
     */
    private function getPaginator(Query $query): Paginator
    {
        $paginator = new Paginator($query, $fetchJoinCollection = false);
        $this->maxcount = $paginator->count();

        return $paginator;
    }

    /**
     * @return ArrayCollection<int, Fellowship>
     */
    public function getEmptyList(): ArrayCollection
    {
        $this->maxcount = 0;

        return new ArrayCollection([]);
    }

    /**
     * @return Paginator<Fellowship>
     */
    public function findFellowshipsByPopularity(): Paginator
    {
        $qb = $this->getQueryBuilder();
        $qb->addSelect('(1+d.nbVotes)/(1+POWER(DATE_DIFF(CURRENT_TIMESTAMP(), d.datePublish), 2)) AS HIDDEN popularity');
        $qb->orderBy('popularity', 'DESC');

        // tie-breaker, for a stable order and pagination
        $qb->addOrderBy('d.id', 'DESC');

        return $this->getPaginator($qb->getQuery());
    }

    /**
     * @return Paginator<Fellowship>
     */
    public function findFellowshipsByAge(): Paginator
    {
        $qb = $this->getQueryBuilder();

        $qb->orderBy('d.datePublish', 'DESC');

        // tie-breaker, for a stable order and pagination
        $qb->addOrderBy('d.id', 'DESC');

        return $this->getPaginator($qb->getQuery());
    }

    /**
     * @return Paginator<Fellowship>
     */
    public function findFellowshipsByRecentDiscussion(): Paginator
    {
        $qb = $this->getQueryBuilder();

        $qb->andWhere('d.nbComments > 0');
        $qb->orderBy('d.dateLastComment', 'DESC');

        // tie-breaker, for a stable order and pagination
        $qb->addOrderBy('d.id', 'DESC');

        return $this->getPaginator($qb->getQuery());
    }

    /**
     * @return Paginator<Fellowship>
     */
    public function findFellowshipsByFavorite(User $user): Paginator
    {
        $qb = $this->getQueryBuilder();

        $qb->leftJoin('d.favorites', 'u');
        $qb->andWhere('u = :user');
        $qb->setParameter('user', $user);
        $qb->orderBy('d.datePublish', 'DESC');

        // tie-breaker, for a stable order and pagination
        $qb->addOrderBy('d.id', 'DESC');

        return $this->getPaginator($qb->getQuery());
    }

    /**
     * @return Paginator<Fellowship>
     */
    public function findFellowshipsByAuthor(User $user): Paginator
    {
        $qb = $this->getQueryBuilder();

        $qb->andWhere('d.user = :user');
        $qb->setParameter('user', $user);
        $qb->orderBy('d.datePublish', 'DESC');

        // tie-breaker, for a stable order and pagination
        $qb->addOrderBy('d.id', 'DESC');

        return $this->getPaginator($qb->getQuery());
    }

    /**
     * @return Paginator<Fellowship>
     */
    public function findFellowshipsInHallOfFame(): Paginator
    {
        $qb = $this->getQueryBuilder();

        $qb->andWhere('d.nbVotes > 10');
        $qb->orderBy('d.nbVotes', 'DESC');

        // tie-breaker, for a stable order and pagination
        $qb->addOrderBy('d.id', 'DESC');

        return $this->getPaginator($qb->getQuery());
    }

    /**
     * @return Paginator<Fellowship>
     */
    public function findFellowshipsInHotTopic(): Paginator
    {
        $qb = $this->getQueryBuilder();

        $qb->addSelect('(SELECT count(c) FROM '.FellowshipComment::class.' c WHERE c.fellowship=d AND DATE_DIFF(CURRENT_TIMESTAMP(), c.dateCreation)<1) AS HIDDEN nbRecentComments');
        $qb->orderBy('nbRecentComments', 'DESC');
        $qb->addOrderBy('d.nbComments', 'DESC');

        // tie-breaker, for a stable order and pagination
        $qb->addOrderBy('d.id', 'DESC');

        return $this->getPaginator($qb->getQuery());
    }

    /**
     * @return Paginator<Fellowship>
     */
    public function findFellowshipsWithComplexSearch(): Paginator
    {
        $request = $this->currentRequest();

        $cards_code = $request->query->all('cards');

        $author_name = StringSanitizer::sanitize($request->query->get('author'));
        $fellowship_name = StringSanitizer::sanitize($request->query->get('name'));
        $nb_decks = intval(filter_var($request->query->get('nb_decks'), FILTER_SANITIZE_NUMBER_INT));
        $numcores = $request->query->get('numcores');
        $numplaysets = $request->query->get('numplaysets');

        $sort = $request->query->get('sort');
        $packs = $request->query->all('packs');

        $customPackCodes = array_values(array_filter((array) $request->query->all('custom_packs'), is_string(...)));

        $qb = $this->getQueryBuilder();
        $joinTables = [];

        if (!empty($author_name)) {
            $qb->innerJoin('d.user', 'u');
            $joinTables[] = 'd.user';
            $qb->andWhere('u.username = :username');
            $qb->setParameter('username', $author_name);
        }

        if (!empty($fellowship_name)) {
            $qb->andWhere('d.name like :fellowname');
            $qb->setParameter('fellowname', "%$fellowship_name%");
        }

        if ($nb_decks) {
            $qb->andWhere('d.nbDecks = :nbdecks');
            $qb->setParameter('nbdecks', $nb_decks);
        }

        $useCustomPacks = [] !== $customPackCodes && $this->user;

        if (count($cards_code) > 0 || count($packs) > 0 || $useCustomPacks) {
            $qb->innerJoin('d.decklists', 'l');
            $qb->innerJoin('l.decklist', 'ld');

            if (count($cards_code) > 0) {
                foreach ($cards_code as $i => $card_code) {
                    /* @var $card \App\Entity\Card */
                    $card = $this->cardRepository->findOneBy(['code' => $card_code]);
                    if (!$card) {
                        continue;
                    }

                    $qb->innerJoin('ld.slots', "s$i");
                    $qb->andWhere("s$i.card = :card$i");
                    $qb->setParameter("card$i", $card);
                    // Add packs containing requested# Add packs containing requested cards
                    // $packs[] = $card->getPack()->getId();
                }
            }

            if (count($packs) > 0 || $useCustomPacks) {
                // A card is "not covered" if it has no printing in the official allowed
                // packs AND is not present in any selected custom pack.
                $sub = $this->doctrine->createQueryBuilder();
                $sub->select('c');
                $sub->from(Card::class, 'c');
                $sub->innerJoin(Decklistslot::class, 's', 'WITH', 's.card = c');
                $sub->where('s.decklist = ld');

                if (count($packs) > 0) {
                    $sub->andWhere('NOT EXISTS (SELECT cpfm.id FROM '.CardPrinting::class.' cpfm WHERE cpfm.card = c AND cpfm.pack IN (:fm_packs))');
                    $qb->setParameter('fm_packs', $packs);
                }

                if ($useCustomPacks) {
                    $sub->andWhere(
                        'NOT EXISTS ('.
                            'SELECT ucpcfm.id FROM '.UserCustomPackCard::class.' ucpcfm '.
                            'JOIN ucpcfm.customPack ucpfm '.
                            'WHERE ucpcfm.card = c '.
                            'AND ucpfm.code IN (:fm_custom_codes) '.
                            'AND ucpfm.user = :fm_custom_user'.
                        ')'
                    );
                    $qb->setParameter('fm_custom_codes', $customPackCodes);
                    $qb->setParameter('fm_custom_user', $this->user);
                }

                $qb->andWhere($qb->expr()->not($qb->expr()->exists($sub->getDQL())));
            }

            // Num cores
            // SELECT fellowship.id, decklistslot.card_id, SUM(decklistslot.quantity), card.quantity FROM (((fellowship INNER JOIN fellowship_decklist ON fellowship.id = fellowship_decklist.fellowship_id) INNER JOIN decklistslot ON fellowship_decklist.decklist_id = decklistslot.decklist_id) INNER JOIN card ON decklistslot.card_id = card.id) WHERE card.pack_id = 1 GROUP BY fellowship.id,decklistslot.card_id HAVING SUM(decklistslot.quantity)>3*card.quantity;
            $sub = $this->doctrine->createQueryBuilder();
            $sub->select('jp.quantity');
            $sub->from(Card::class, 'j');
            $sub->innerJoin(CardPrinting::class, 'jp', 'WITH', 'jp.card = j AND jp.pack = 1'); // Match Core Set printing
            $sub->innerJoin(Decklistslot::class, 'dls', 'WITH', 'dls.card = j');
            $sub->innerJoin(FellowshipDecklist::class, 'fdl', 'WITH', 'fdl.decklist = dls.decklist');
            $sub->where('fdl.fellowship = d');
            $sub->groupBy('d.id, dls.card, jp.quantity');
            $sub->having('SUM(dls.quantity) > :numcores * jp.quantity');
            $qb->setParameter('numcores', $numcores);
            $qb->andWhere($qb->expr()->not($qb->expr()->exists($sub->getDQL())));

            $sub = $this->doctrine->createQueryBuilder();
            $sub->select('jp2.quantity');
            $sub->from(Card::class, 'j2');
            $sub->innerJoin(CardPrinting::class, 'jp2', 'WITH', 'jp2.card = j2 AND jp2.pack = 1'); // Match Core Set printing
            $sub->innerJoin(Decklistslot::class, 'dls2', 'WITH', 'dls2.card = j2');
            $sub->innerJoin(FellowshipDecklist::class, 'fdl2', 'WITH', 'fdl2.decklist = dls2.decklist');
            $sub->where('fdl2.fellowship = d');
            $sub->groupBy('d.id, dls2.card, jp2.quantity');
            $sub->having('SUM(dls2.quantity) > :numplaysets * jp2.quantity');
            $qb->setParameter('numplaysets', $numplaysets);
            $qb->andWhere($qb->expr()->not($qb->expr()->exists($sub->getDQL())));
        }

        switch ($sort) {
            case 'date':
                $qb->orderBy('d.datePublish', 'DESC');
                break;

            case 'likes':
                $qb->orderBy('d.nbVotes', 'DESC');
                break;

            case 'reputation':
                if (!in_array('d.user', $joinTables, true)) {
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
