<?php

namespace AppBundle\Model;

use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Router;
use Psr\Log\LoggerInterface;
use AppBundle\Entity\User;
use Doctrine\ORM\Query;
use Doctrine\ORM\Tools\Pagination\Paginator;
use AppBundle\Entity\Sphere;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * The job of this class is to find and return decklists
 * @author alsciende
 * @property integer $maxcount Number of found rows for last request
 *
 */
class DecklistManager {
	protected $predominantSphere;
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

	public function setPredominantSphere(Sphere $predominantSphere = null) {
		$this->predominantSphere = $predominantSphere;
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
		$qb->from('AppBundle:Decklist', 'd');

        if ($this->predominantSphere) {
            $qb->where('d.predominantSphere = :predominantSphere');
            $qb->setParameter('predominantSphere', $this->predominantSphere);
        }
		$qb->setFirstResult($this->start);
		$qb->setMaxResults($this->limit);
		$qb->distinct();

		return $qb;
	}

    private function getPaginator(Query $query) {
        $paginator = new Paginator($query, $fetchJoinCollection = false);
        $this->maxcount = $paginator->count();

        return $paginator;
    }

    public function getEmptyList() {
        $this->maxcount = 0;

        return new ArrayCollection([]);
    }

    public function findDecklistsByPopularity() {
        $qb = $this->getQueryBuilder();
        $qb->addSelect('(1+d.nbVotes)/(1+POWER(DATE_DIFF(CURRENT_TIMESTAMP(), d.dateCreation), 2)) AS HIDDEN popularity');
        $qb->orderBy('popularity', 'DESC');

        return $this->getPaginator($qb->getQuery());
    }

    public function findDecklistsByAge() {
        $qb = $this->getQueryBuilder();

        $qb->orderBy('d.dateCreation', 'DESC');

        return $this->getPaginator($qb->getQuery());
    }

    public function findDecklistsByRecentDiscussion() {
        $qb = $this->getQueryBuilder();

        $qb->andWhere('d.nbComments > 0');
        $qb->orderBy('d.dateLastComment', 'DESC');
        return $this->getPaginator($qb->getQuery());
    }

    public function findDecklistsByFavorite(User $user) {
        $qb = $this->getQueryBuilder();

        $qb->leftJoin('d.favorites', 'u');
        $qb->andWhere('u = :user');
        $qb->setParameter('user', $user);
        $qb->orderBy('d.dateCreation', 'DESC');

        return $this->getPaginator($qb->getQuery());
    }

    public function findDecklistsByAuthor(User $user) {
        $qb = $this->getQueryBuilder();

        $qb->andWhere('d.user = :user');
        $qb->setParameter('user', $user);
        $qb->orderBy('d.dateCreation', 'DESC');

        return $this->getPaginator($qb->getQuery());
    }

    public function findDecklistsInHallOfFame() {
        $qb = $this->getQueryBuilder();

        $qb->andWhere('d.nbVotes > 10');
        $qb->orderBy('d.nbVotes', 'DESC');

        return $this->getPaginator($qb->getQuery());
    }

    public function findDecklistsInHotTopic() {
        $qb = $this->getQueryBuilder();

        $qb->addSelect('(SELECT count(c) FROM AppBundle:Comment c WHERE c.decklist=d AND DATE_DIFF(CURRENT_TIMESTAMP(), c.dateCreation)<1) AS HIDDEN nbRecentComments');
        $qb->orderBy('nbRecentComments', 'DESC');
        $qb->orderBy('d.nbComments', 'DESC');

        return $this->getPaginator($qb->getQuery());
    }

    public function findDecklistsWithComplexSearch() {
        $request = $this->request_stack->getCurrentRequest();

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
            $sphere = $this->doctrine->getRepository('AppBundle:Sphere')->findOneBy(['code' => $sphere_code]);
        }

        $numcores = $request->query->get('numcores');

        $author_name = filter_var($request->query->get('author'), FILTER_SANITIZE_STRING);

        $decklist_name = filter_var($request->query->get('name'), FILTER_SANITIZE_STRING);

        $sort = $request->query->get('sort');

        $packs = $request->query->get('packs');

        $threat_op = $request->query->get('threato');
        $threat = $request->query->get('threat');

        $reputation_op = $request->query->get('reputationo');
        $reputation = $request->query->get('reputation');

        $require_description = $request->query->get('require_description');

        $qb = $this->getQueryBuilder();
        $joinTables = [];

        if (!empty($sphere)) {
            $qb->innerJoin('d.spheres', "w");
            $qb->andWhere("w.id = :sphere");
            $qb->setParameter("sphere", $sphere->getId());
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
            if ($threat_op == '>') {
                $qb->andWhere('d.startingThreat > :threat');
            } elseif ($threat_op == '<') {
                $qb->andWhere('d.startingThreat < :threat');
            } else {
                $qb->andWhere('d.startingThreat = :threat');
            }
            $qb->setParameter('threat', $threat);
        }

        if (!empty($reputation) && is_numeric($reputation)) {
            $qb->innerJoin('d.user', 'u');
            if ($reputation_op == '>') {
                $qb->andWhere('u.reputation > :reputation');
            } elseif ($reputation_op == '<') {
                $qb->andWhere('u.reputation < :reputation');
            } else {
                $qb->andWhere('u.reputation = :reputation');
            }
            $qb->setParameter('reputation', $reputation);
        }

        if ($require_description) {
            $qb->andWhere($qb->expr()->gt($qb->expr()->length('d.descriptionHtml'),0));
        }

        if (!empty($cards_code) || !empty($packs)) {
            if (!empty($cards_code)) {
                foreach ($cards_code as $i => $card_code) {
                    /* @var $card \AppBundle\Entity\Card */
                    $card = $this->doctrine->getRepository('AppBundle:Card')->findOneBy(['code' => $card_code]);
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
            if (!empty($packs)) {
                $sub = $this->doctrine->createQueryBuilder();
                $sub->select("c");
                $sub->from("AppBundle:Card", "c");
                $sub->innerJoin('AppBundle:Decklistslot', 's', 'WITH', 's.card = c');
                $sub->where('s.decklist = d');
                $sub->andWhere($sub->expr()->notIn('c.pack', $packs));
                $qb->andWhere($qb->expr()->not($qb->expr()->exists($sub->getDQL())));
            }
            if (!empty($cards_to_exclude)) {
                $sub = $this->doctrine->createQueryBuilder();
                $sub->select("k");
                $sub->from("AppBundle:Card", "k");
                $sub->innerJoin('AppBundle:Decklistslot', 't', 'WITH', 't.card = k');
                $sub->where('t.decklist = d');
                $sub->andWhere($sub->expr()->in('k.code', $cards_to_exclude));
                $qb->andWhere($qb->expr()->not($qb->expr()->exists($sub->getDQL())));
            }

            // Num cores
            $sub = $this->doctrine->createQueryBuilder();
            $sub->select("j");
            $sub->from("AppBundle:Card", "j");
            $sub->innerJoin('AppBundle:Decklistslot', 'v', 'WITH', 'v.card = j');
            $sub->where('v.decklist = d');
            $sub->andWhere('j.type <> 1'); // Don't match heroes
            $sub->andWhere('j.pack = 1'); // Match Core Set
            $sub->andWhere('v.quantity > j.quantity * :numcores');
            $qb->setParameter('numcores', $numcores);
            $qb->andWhere($qb->expr()->not($qb->expr()->exists($sub->getDQL())));
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
