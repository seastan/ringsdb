<?php

namespace AppBundle\Model;

use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Router;
use AppBundle\Services\DeckInterface;
use Psr\Log\LoggerInterface;
use AppBundle\Entity\User;
use Doctrine\ORM\Query;
use Doctrine\ORM\Tools\Pagination\Paginator;
use AppBundle\Entity\Sphere;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * The job of this class is to find and return fellowships
 * @author alsciende
 * @property integer $maxcount Number of found rows for last request
 *
 */
class FellowshipManager {
	protected $page = 1;
	protected $start = 0;
	protected $limit = 30;
	protected $maxcount = 0;

	public function __construct(EntityManager $doctrine, RequestStack $request_stack, Router $router, LoggerInterface $logger) {
		$this->doctrine = $doctrine;
		$this->request_stack = $request_stack;
		$this->router = $router;
		$this->logger = $logger;
	}

	public function setLimit($limit) {
		$this->limit = $limit;
	}

	public function setPage($page) {
		$this->page = max($page, 1);
		$this->start = ($this->page - 1) * $this->limit;
	}

	public function getMaxCount() {
		return $this->maxcount;
	}

	/**
	 * creates the basic query builder and initializes it
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
     */
    private function getPaginator(Query $query) {
        $paginator = new Paginator($query, $fetchJoinCollection = false);
        $this->maxcount = $paginator->count();

        return $paginator;
    }

    public function getEmptyList() {
        $this->maxcount = 0;

        return new ArrayCollection([]);
    }

    public function findFellowshipsByPopularity() {
        $qb = $this->getQueryBuilder();
        $qb->addSelect('(1+d.nbVotes)/(1+POWER(DATE_DIFF(CURRENT_TIMESTAMP(), d.datePublish), 2)) AS HIDDEN popularity');
        $qb->orderBy('popularity', 'DESC');

        return $this->getPaginator($qb->getQuery());
    }

    public function findFellowshipsByAge($ignoreEmptyDescriptions = false) {
        $qb = $this->getQueryBuilder();

        $qb->orderBy('d.datePublish', 'DESC');

        return $this->getPaginator($qb->getQuery());
    }

    public function findFellowshipsByRecentDiscussion($ignoreEmptyDescriptions = false) {
        $qb = $this->getQueryBuilder();

        $qb->andWhere('d.nbComments > 0');
        $qb->orderBy('d.dateLastComment', 'DESC');

        return $this->getPaginator($qb->getQuery());
    }

    public function findFellowshipsByFavorite(User $user) {
        $qb = $this->getQueryBuilder();

        $qb->leftJoin('d.favorites', 'u');
        $qb->andWhere('u = :user');
        $qb->setParameter('user', $user);
        $qb->orderBy('d.datePublish', 'DESC');

        return $this->getPaginator($qb->getQuery());
    }

    public function findFellowshipsByAuthor(User $user) {
        $qb = $this->getQueryBuilder();

        $qb->andWhere('d.user = :user');
        $qb->setParameter('user', $user);
        $qb->orderBy('d.datePublish', 'DESC');

        return $this->getPaginator($qb->getQuery());
    }

    public function findFellowshipsInHallOfFame() {
        $qb = $this->getQueryBuilder();

        $qb->andWhere('d.nbVotes > 10');
        $qb->orderBy('d.nbVotes', 'DESC');

        return $this->getPaginator($qb->getQuery());
    }

    public function findFellowshipsInHotTopic() {
        $qb = $this->getQueryBuilder();

        $qb->addSelect('(SELECT count(c) FROM AppBundle:FellowshipComment c WHERE c.fellowship=d AND DATE_DIFF(CURRENT_TIMESTAMP(), c.dateCreation)<1) AS HIDDEN nbRecentComments');
        $qb->orderBy('nbRecentComments', 'DESC');
        $qb->orderBy('d.nbComments', 'DESC');

        return $this->getPaginator($qb->getQuery());
    }

    public function findFellowshipsWithComplexSearch() {
        $request = $this->request_stack->getCurrentRequest();

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

        if (!empty($cards_code) || !empty($packs)) {
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
            if (!empty($packs)) {
                $sub = $this->doctrine->createQueryBuilder();
                $sub->select("c");
                $sub->from("AppBundle:Card", "c");
                $sub->innerJoin('AppBundle:Decklistslot', 's', 'WITH', 's.card = c');
                $sub->where('s.decklist = ld');
                $sub->andWhere($sub->expr()->notIn('c.pack', $packs));

                $qb->andWhere($qb->expr()->not($qb->expr()->exists($sub->getDQL())));
            }

            // Num cores
            // SELECT fellowship.id, decklistslot.card_id, SUM(decklistslot.quantity), card.quantity FROM (((fellowship INNER JOIN fellowship_decklist ON fellowship.id = fellowship_decklist.fellowship_id) INNER JOIN decklistslot ON fellowship_decklist.decklist_id = decklistslot.decklist_id) INNER JOIN card ON decklistslot.card_id = card.id) WHERE card.pack_id = 1 GROUP BY fellowship.id,decklistslot.card_id HAVING SUM(decklistslot.quantity)>3*card.quantity;
            $sub = $this->doctrine->createQueryBuilder();
            $sub->select("j");
            $sub->select("j.quantity");
            $sub->from("AppBundle:Card", "j");
            $sub->innerJoin('AppBundle:Decklistslot', 'dls', 'WITH', 'dls.card = j');
            $sub->innerJoin('AppBundle:FellowshipDecklist', 'fdl', 'WITH', 'fdl.decklist = dls.decklist');
            $sub->where('fdl.fellowship = d');
            $sub->andWhere('j.pack = 1'); # Match Core Set
            $sub->groupBy('d.id,dls.card');
            $sub->having('SUM(dls.quantity) > :numcores * j.quantity');
            $qb->setParameter("numcores", $numcores);
            $qb->andWhere($qb->expr()->not($qb->expr()->exists($sub->getDQL())));

            $sub = $this->doctrine->createQueryBuilder();
            $sub->select("j2");
            $sub->select("j2.quantity");
            $sub->from("AppBundle:Card", "j2");
            $sub->innerJoin('AppBundle:Decklistslot', 'dls2', 'WITH', 'dls2.card = j2');
            $sub->innerJoin('AppBundle:FellowshipDecklist', 'fdl2', 'WITH', 'fdl2.decklist = dls2.decklist');
            $sub->where('fdl2.fellowship = d');
            $sub->andWhere('j2.pack = 1'); # Match Core Set
            $sub->groupBy('d.id,dls2.card');
            $sub->having('SUM(dls2.quantity) > :numplaysets * j2.quantity');
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
                $qb->orderBy('u.reputation', 'DESC');
                break;

            case 'popularity':
            default:
                $qb->addSelect('(1+d.nbVotes)/(1+POWER(DATE_DIFF(CURRENT_TIMESTAMP(), d.dateCreation), 2)) AS HIDDEN popularity');
                $qb->orderBy('popularity', 'DESC');
                break;
        }

        return $this->getPaginator($qb->getQuery());
    }

    public function getNumberOfPages() {
        return intval(ceil($this->maxcount / $this->limit));
    }

    public function getAllPages() {
        $request = $this->request_stack->getCurrentRequest();
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

    public function getPreviousUrl() {
        if ($this->page === 1) {
            return null;
        }

        $request = $this->request_stack->getCurrentRequest();
        $route = $request->get('_route');
        $route_params = $request->get('_route_params');

        $query = $request->query->all();
        $params = $query + $route_params;

        $previous_page = max(1, $this->page - 1);
        $params['page'] = $previous_page;

        return $this->router->generate($route, $params);
    }

    public function getNextUrl() {
        if ($this->page === $this->getNumberOfPages()) {
            return null;
        }

        $request = $this->request_stack->getCurrentRequest();
        $route = $request->get('_route');
        $route_params = $request->get('_route_params');

        $query = $request->query->all();
        $params = $query + $route_params;

        $next_page = min($this->getNumberOfPages(), $this->page + 1);
        $params['page'] = $next_page;

        return $this->router->generate($route, $params);
    }
}
