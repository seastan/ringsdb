<?php

declare(strict_types=1);

namespace App\Model;

use App\Entity\Card;
use App\Entity\Questlog;
use App\Entity\User;
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
 * The job of this class is to find and return questlogs.
 *
 * @author seastan
 *
 * @property int $maxcount Number of found rows for last request
 */
class QuestLogManager
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

    public function __construct(EntityManagerInterface $doctrine, RequestStack $request_stack, UrlGeneratorInterface $router, CardRepository $cardRepository)
    {
        $this->doctrine = $doctrine;
        $this->request_stack = $request_stack;
        $this->router = $router;
        $this->cardRepository = $cardRepository;
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
        $qb->from('App:Questlog', 'd');
        $qb->andWhere('d.isPublic = 1');
        $qb->setFirstResult($this->start);
        $qb->setMaxResults($this->limit);
        $qb->distinct();

        return $qb;
    }

    /**
     * creates the paginator around the query.
     *
     * @param Query<mixed, Questlog> $query
     *
     * @return Paginator<Questlog>
     */
    private function getPaginator(Query $query): Paginator
    {
        $paginator = new Paginator($query, $fetchJoinCollection = false);
        $this->maxcount = $paginator->count();

        return $paginator;
    }

    /**
     * @return ArrayCollection<int, Questlog>
     */
    public function getEmptyList(): ArrayCollection
    {
        $this->maxcount = 0;

        return new ArrayCollection([]);
    }

    /**
     * @return Paginator<Questlog>
     */
    public function findQuestLogsByPopularity(): Paginator
    {
        $qb = $this->getQueryBuilder();
        $qb->addSelect('(1+d.nbVotes)/(1+POWER(DATE_DIFF(CURRENT_TIMESTAMP(), d.datePublish), 2)) AS HIDDEN popularity');
        $qb->orderBy('popularity', 'DESC');

        // tie-breaker, for a stable order and pagination
        $qb->addOrderBy('d.id', 'DESC');

        return $this->getPaginator($qb->getQuery());
    }

    /**
     * @return Paginator<Questlog>
     */
    public function findQuestLogsByAge(): Paginator
    {
        $qb = $this->getQueryBuilder();

        $qb->orderBy('d.datePublish', 'DESC');

        // tie-breaker, for a stable order and pagination
        $qb->addOrderBy('d.id', 'DESC');

        return $this->getPaginator($qb->getQuery());
    }

    /**
     * @return Paginator<Questlog>
     */
    public function findQuestLogsByFavorite(User $user): Paginator
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
     * @return Paginator<Questlog>
     */
    public function findQuestLogsByAuthor(User $user): Paginator
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
     * @return Paginator<Questlog>
     */
    public function findQuestLogsInHallOfFame(): Paginator
    {
        $qb = $this->getQueryBuilder();

        $qb->andWhere('d.nbVotes > 10');
        $qb->orderBy('d.nbVotes', 'DESC');

        // tie-breaker, for a stable order and pagination
        $qb->addOrderBy('d.id', 'DESC');

        return $this->getPaginator($qb->getQuery());
    }

    /**
     * @return Paginator<Questlog>
     */
    public function findQuestLogsInHotTopic(): Paginator
    {
        $qb = $this->getQueryBuilder();

        $qb->addSelect('(SELECT count(c) FROM App:QuestlogComment c WHERE c.questlog=d AND DATE_DIFF(CURRENT_TIMESTAMP(), c.dateCreation)<1) AS HIDDEN nbRecentComments');
        $qb->orderBy('nbRecentComments', 'DESC');
        $qb->addOrderBy('d.nbComments', 'DESC');

        // tie-breaker, for a stable order and pagination
        $qb->addOrderBy('d.id', 'DESC');

        return $this->getPaginator($qb->getQuery());
    }

    /**
     * @return Paginator<Questlog>
     */
    public function findQuestLogsWithComplexSearch(): Paginator
    {
        $request = $this->currentRequest();

        $cards_code = $request->query->all('cards');

        $author_name = filter_var($request->query->get('author'), FILTER_SANITIZE_STRING);
        $questlog_name = filter_var($request->query->get('name'), FILTER_SANITIZE_STRING);
        $nb_decks = intval(filter_var($request->query->get('nb_decks'), FILTER_SANITIZE_NUMBER_INT));

        $sort = $request->query->get('sort');
        $packs = $request->query->all('packs');

        $customPackCodes = array_values(array_filter($request->query->all('custom_packs'), 'is_string'));

        $qb = $this->getQueryBuilder();
        $joinTables = [];

        if (!empty($author_name)) {
            $qb->innerJoin('d.user', 'u');
            $joinTables[] = 'd.user';
            $qb->andWhere('u.username = :username');
            $qb->setParameter('username', $author_name);
        }

        if (!empty($questlog_name)) {
            $qb->andWhere('d.name like :logname');
            $qb->setParameter('logname', "%$questlog_name%");
        }

        if ($nb_decks) {
            $qb->andWhere('d.nbDecks = :nbdecks');
            $qb->setParameter('nbdecks', $nb_decks);
        }

        $useCustomPacks = !empty($customPackCodes) && $this->user;

        if (count($cards_code) > 0 || count($packs) > 0 || $useCustomPacks) {
            $qb->innerJoin('d.decks', 'l');
            $qb->innerJoin('l.deck', 'ld');

            if (count($cards_code) > 0) {
                foreach ($cards_code as $i => $card_code) {
                    /* @var $card Card */
                    $card = $this->cardRepository->findOneBy(['code' => $card_code]);
                    if (!$card) {
                        continue;
                    }

                    $qb->innerJoin('ld.slots', "s$i");
                    $qb->andWhere("s$i.card = :card$i");
                    $qb->setParameter("card$i", $card);

                    // $packs[] = $card->getPack()->getId();
                }
            }
            if (count($packs) > 0 || $useCustomPacks) {
                $sub = $this->doctrine->createQueryBuilder();
                $sub->select('c');
                $sub->from('App:Card', 'c');
                $sub->innerJoin('App:Deckslot', 's', 'WITH', 's.card = c');
                $sub->where('s.deck = ld');

                if (count($packs) > 0) {
                    $sub->andWhere('NOT EXISTS (SELECT cpqlm.id FROM App:CardPrinting cpqlm WHERE cpqlm.card = c AND cpqlm.pack IN (:qlm_packs))');
                    $qb->setParameter('qlm_packs', $packs);
                }

                if ($useCustomPacks) {
                    $sub->andWhere(
                        'NOT EXISTS ('.
                            'SELECT ucpcqlm.id FROM App:UserCustomPackCard ucpcqlm '.
                            'JOIN ucpcqlm.customPack ucpqlm '.
                            'WHERE ucpcqlm.card = c '.
                            'AND ucpqlm.code IN (:qlm_custom_codes) '.
                            'AND ucpqlm.user = :qlm_custom_user'.
                        ')'
                    );
                    $qb->setParameter('qlm_custom_codes', $customPackCodes);
                    $qb->setParameter('qlm_custom_user', $this->user);
                }

                $qb->andWhere($qb->expr()->not($qb->expr()->exists($sub->getDQL())));
            }
        }

        switch ($sort) {
            case 'date':
                $qb->orderBy('d.datePublish', 'DESC');
                break;

            case 'likes':
                $qb->orderBy('d.nbVotes', 'DESC');
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
