<?php

namespace AppBundle\Model;

use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Router;
use AppBundle\Entity\User;
use Doctrine\ORM\Query;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * The job of this class is to find and return fellowships
 * @author alsciende
 * @property integer $maxcount Number of found rows for last request
 *
 */
class FellowshipManager {
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
	 * @var \AppBundle\Entity\User|null
	 */
	protected $user = null;

	/**
	 * @var EntityManager
	 */
	private $doctrine;

	/**
	 * @var RequestStack
	 */
	private $request_stack;

	/**
	 * @var Router
	 */
	private $router;

	public function __construct(EntityManager $doctrine, RequestStack $request_stack, Router $router) {
		$this->doctrine = $doctrine;
		$this->request_stack = $request_stack;
		$this->router = $router;
	}

	/**
	 * The current request: the searches and the pagination read its parameters.
	 */
	private function currentRequest(): Request {
		$request = $this->request_stack->getCurrentRequest();
		if ($request === null) {
			throw new \LogicException('No current request.');
		}

		return $request;
	}

	/**
	 * @param mixed $user
	 * @return void
	 */
	public function setUser($user) {
		$this->user = $user;
	}

	/**
	 * @param mixed $limit
	 * @return void
	 */
	public function setLimit($limit) {
		$this->limit = $limit;
	}

	/**
	 * @param mixed $page
	 * @return void
	 */
	public function setPage($page) {
		$this->page = max($page, 1);
		$this->start = ($this->page - 1) * $this->limit;
	}

	/**
	 * @return int
	 */
	public function getMaxCount() {
		return $this->maxcount;
	}

	/**
	 * creates the basic query builder and initializes it
	 * @return \Doctrine\ORM\QueryBuilder
	 */
	private function getQueryBuilder() {
		$qb = $this->doctrine->createQueryBuilder();
		$qb->select('d');
		$qb->from('AppBundle:Fellowship', 'd');
        $qb->andWhere('d.isPublic = 1');
        $qb->setFirstResult($this->start);
		$qb->setMaxResults($this->limit);
		$qb->distinct();

		return $qb;
	}

    /**
     * creates the paginator around the query
     *
     * @param Query $query
     * @return \Doctrine\ORM\Tools\Pagination\Paginator
     */
    private function getPaginator(Query $query) {
        $paginator = new Paginator($query, $fetchJoinCollection = false);
        $this->maxcount = $paginator->count();

        return $paginator;
    }

    /**
     * @return \Doctrine\Common\Collections\ArrayCollection
     */
    public function getEmptyList() {
        $this->maxcount = 0;

        return new ArrayCollection([]);
    }

    /**
     * @return \Doctrine\ORM\Tools\Pagination\Paginator
     */
    public function findFellowshipsByPopularity() {
        $qb = $this->getQueryBuilder();
        $qb->addSelect('(1+d.nbVotes)/(1+POWER(DATE_DIFF(CURRENT_TIMESTAMP(), d.datePublish), 2)) AS HIDDEN popularity');
        $qb->orderBy('popularity', 'DESC');

        // tie-breaker, for a stable order and pagination
        $qb->addOrderBy('d.id', 'DESC');

        return $this->getPaginator($qb->getQuery());
    }

    /**
     * @return \Doctrine\ORM\Tools\Pagination\Paginator
     */
    public function findFellowshipsByAge() {
        $qb = $this->getQueryBuilder();

        $qb->orderBy('d.datePublish', 'DESC');

        // tie-breaker, for a stable order and pagination
        $qb->addOrderBy('d.id', 'DESC');

        return $this->getPaginator($qb->getQuery());
    }

    /**
     * @return \Doctrine\ORM\Tools\Pagination\Paginator
     */
    public function findFellowshipsByRecentDiscussion() {
        $qb = $this->getQueryBuilder();

        $qb->andWhere('d.nbComments > 0');
        $qb->orderBy('d.dateLastComment', 'DESC');

        // tie-breaker, for a stable order and pagination
        $qb->addOrderBy('d.id', 'DESC');

        return $this->getPaginator($qb->getQuery());
    }

    /**
     * @return \Doctrine\ORM\Tools\Pagination\Paginator
     */
    public function findFellowshipsByFavorite(User $user) {
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
     * @return \Doctrine\ORM\Tools\Pagination\Paginator
     */
    public function findFellowshipsByAuthor(User $user) {
        $qb = $this->getQueryBuilder();

        $qb->andWhere('d.user = :user');
        $qb->setParameter('user', $user);
        $qb->orderBy('d.datePublish', 'DESC');

        // tie-breaker, for a stable order and pagination
        $qb->addOrderBy('d.id', 'DESC');

        return $this->getPaginator($qb->getQuery());
    }

    /**
     * @return \Doctrine\ORM\Tools\Pagination\Paginator
     */
    public function findFellowshipsInHallOfFame() {
        $qb = $this->getQueryBuilder();

        $qb->andWhere('d.nbVotes > 10');
        $qb->orderBy('d.nbVotes', 'DESC');

        // tie-breaker, for a stable order and pagination
        $qb->addOrderBy('d.id', 'DESC');

        return $this->getPaginator($qb->getQuery());
    }

    /**
     * @return \Doctrine\ORM\Tools\Pagination\Paginator
     */
    public function findFellowshipsInHotTopic() {
        $qb = $this->getQueryBuilder();

        $qb->addSelect('(SELECT count(c) FROM AppBundle:FellowshipComment c WHERE c.fellowship=d AND DATE_DIFF(CURRENT_TIMESTAMP(), c.dateCreation)<1) AS HIDDEN nbRecentComments');
        $qb->orderBy('nbRecentComments', 'DESC');
        $qb->addOrderBy('d.nbComments', 'DESC');

        // tie-breaker, for a stable order and pagination
        $qb->addOrderBy('d.id', 'DESC');

        return $this->getPaginator($qb->getQuery());
    }

    /**
     * @return \Doctrine\ORM\Tools\Pagination\Paginator
     */
    public function findFellowshipsWithComplexSearch() {
        $request = $this->currentRequest();

        $cards_code = $request->query->get('cards');
        if (!is_array($cards_code)) {
            $cards_code = [];
        }

        $author_name = filter_var($request->query->get('author'), FILTER_SANITIZE_STRING);
        $fellowship_name = filter_var($request->query->get('name'), FILTER_SANITIZE_STRING);
        $nb_decks = intval(filter_var($request->query->get('nb_decks'), FILTER_SANITIZE_NUMBER_INT));
        $numcores = $request->query->get('numcores');
        $numplaysets = $request->query->get('numplaysets');

        $sort = $request->query->get('sort');
        $packs = $request->query->get('packs');
        if (!is_array($packs)) {
            $packs = [];
        }

        $customPackCodes = array_values(array_filter((array) $request->query->get('custom_packs', []), 'is_string'));

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

        $useCustomPacks = !empty($customPackCodes) && $this->user;

        if (!empty($cards_code) || !empty($packs) || $useCustomPacks) {
            $qb->innerJoin('d.decklists', "l");
            $qb->innerJoin('l.decklist', "ld");

            if (!empty($cards_code)) {
                foreach ($cards_code as $i => $card_code) {
                    /* @var $card \AppBundle\Entity\Card */
                    $card = $this->doctrine->getRepository('AppBundle:Card')->findOneBy(['code' => $card_code]);
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
            if (!empty($packs) || $useCustomPacks) {
                // A card is "not covered" if it has no printing in the official allowed
                // packs AND is not present in any selected custom pack.
                $sub = $this->doctrine->createQueryBuilder();
                $sub->select("c");
                $sub->from("AppBundle:Card", "c");
                $sub->innerJoin('AppBundle:Decklistslot', 's', 'WITH', 's.card = c');
                $sub->where('s.decklist = ld');

                if (!empty($packs)) {
                    $sub->andWhere("NOT EXISTS (SELECT cpfm.id FROM AppBundle:CardPrinting cpfm WHERE cpfm.card = c AND cpfm.pack IN (:fm_packs))");
                    $qb->setParameter('fm_packs', $packs);
                }

                if ($useCustomPacks) {
                    $sub->andWhere(
                        "NOT EXISTS (" .
                            "SELECT ucpcfm.id FROM AppBundle:UserCustomPackCard ucpcfm " .
                            "JOIN ucpcfm.customPack ucpfm " .
                            "WHERE ucpcfm.card = c " .
                            "AND ucpfm.code IN (:fm_custom_codes) " .
                            "AND ucpfm.user = :fm_custom_user" .
                        ")"
                    );
                    $qb->setParameter('fm_custom_codes', $customPackCodes);
                    $qb->setParameter('fm_custom_user', $this->user);
                }

                $qb->andWhere($qb->expr()->not($qb->expr()->exists($sub->getDQL())));
            }

            // Num cores
            // SELECT fellowship.id, decklistslot.card_id, SUM(decklistslot.quantity), card.quantity FROM (((fellowship INNER JOIN fellowship_decklist ON fellowship.id = fellowship_decklist.fellowship_id) INNER JOIN decklistslot ON fellowship_decklist.decklist_id = decklistslot.decklist_id) INNER JOIN card ON decklistslot.card_id = card.id) WHERE card.pack_id = 1 GROUP BY fellowship.id,decklistslot.card_id HAVING SUM(decklistslot.quantity)>3*card.quantity;
            $sub = $this->doctrine->createQueryBuilder();
            $sub->select("jp.quantity");
            $sub->from("AppBundle:Card", "j");
            $sub->innerJoin('AppBundle:CardPrinting', 'jp', 'WITH', 'jp.card = j AND jp.pack = 1'); # Match Core Set printing
            $sub->innerJoin('AppBundle:Decklistslot', 'dls', 'WITH', 'dls.card = j');
            $sub->innerJoin('AppBundle:FellowshipDecklist', 'fdl', 'WITH', 'fdl.decklist = dls.decklist');
            $sub->where('fdl.fellowship = d');
            $sub->groupBy('d.id, dls.card, jp.quantity');
            $sub->having('SUM(dls.quantity) > :numcores * jp.quantity');
            $qb->setParameter("numcores", $numcores);
            $qb->andWhere($qb->expr()->not($qb->expr()->exists($sub->getDQL())));

            $sub = $this->doctrine->createQueryBuilder();
            $sub->select("jp2.quantity");
            $sub->from("AppBundle:Card", "j2");
            $sub->innerJoin('AppBundle:CardPrinting', 'jp2', 'WITH', 'jp2.card = j2 AND jp2.pack = 1'); # Match Core Set printing
            $sub->innerJoin('AppBundle:Decklistslot', 'dls2', 'WITH', 'dls2.card = j2');
            $sub->innerJoin('AppBundle:FellowshipDecklist', 'fdl2', 'WITH', 'fdl2.decklist = dls2.decklist');
            $sub->where('fdl2.fellowship = d');
            $sub->groupBy('d.id, dls2.card, jp2.quantity');
            $sub->having('SUM(dls2.quantity) > :numplaysets * jp2.quantity');
            $qb->setParameter("numplaysets", $numplaysets);
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

    /**
     * @return int
     */
    public function getNumberOfPages() {
        return intval(ceil($this->maxcount / $this->limit));
    }

    /**
     * @return array
     */
    public function getAllPages() {
        $request = $this->currentRequest();
        $route = $request->get('_route');
        $route_params = $request->get('_route_params');
        $query = $request->query->all();

        $params = $query + $route_params;

        $number_of_pages = $this->getNumberOfPages();
        $pages = [];
        for ($page = 1; $page <= $number_of_pages; $page++) {
            $pages[] = [
                "numero" => $page,
                "url" => $this->router->generate($route, ["page" => $page] + $params),
                "current" => $page == $this->page
            ];
        }

        return $pages;
    }

    /**
     * @return array<int, mixed>
     */
    public function getClosePages() {
        $allPages = $this->getAllPages();
        $numero_courant = $this->page - 1;
        $pages = [];
        foreach ($allPages as $numero => $page) {
            if ($numero === 0 || $numero === count($allPages) - 1 || abs($numero - $numero_courant) <= 2) {
                $pages[] = $page;
            }
        }

        return $pages;
    }

    /**
     * @return string|null
     */
    public function getPreviousUrl() {
        if ($this->page === 1) {
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

    /**
     * @return string|null
     */
    public function getNextUrl() {
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
