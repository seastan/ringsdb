<?php

namespace App\Controller\Review;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ListReviewsController extends AbstractController
{
    private int $cacheExpiration;
    private EntityManagerInterface $entityManager;

    public function __construct(
        int $cacheExpiration,
        EntityManagerInterface $entityManager
    ) {
        $this->cacheExpiration = $cacheExpiration;
        $this->entityManager = $entityManager;
    }

    /**
     * @Route(
     *     "/reviews/{page}",
     *     name="card_reviews_list",
     *     requirements={"page"="\d+"},
     *     defaults={"page"=1}
     * )
     */
    public function listAction(Request $request, int $page = 1): Response
    {
        $response = new Response();
        $response->setPublic();
        $response->setMaxAge($this->cacheExpiration);
        $limit = 5;
        if ($page < 1) {
            $page = 1;
        }
        $start = ($page - 1) * $limit;
        $pagetitle = 'Card Reviews';
        /* @var $em EntityManager */
        $dql = 'SELECT DISTINCT r FROM App:Review r JOIN r.card c JOIN c.printings cp JOIN cp.pack p WHERE p.dateRelease IS NOT NULL ORDER BY r.dateCreation DESC, r.id DESC';
        $query = $this->entityManager->createQuery($dql)->setFirstResult($start)->setMaxResults($limit);
        $paginator = new Paginator($query, false);
        $maxcount = count($paginator);
        $reviews = [];
        foreach ($paginator as $review) {
            $reviews[] = $review;
        }
        // pagination : calcul de nbpages // currpage // prevpage // nextpage
        // à partir de $start, $limit, $count, $maxcount, $page
        $currpage = $page;
        $prevpage = max(1, $currpage - 1);
        $nbpages = min(10, ceil($maxcount / $limit));
        $nextpage = min($nbpages, $currpage + 1);
        $route = $request->get('_route');
        $params = $request->query->all();
        $pages = [];
        for ($page = 1; $page <= $nbpages; ++$page) {
            $pages[] = ['numero' => $page, 'url' => $this->generateUrl($route, $params + ['page' => $page]), 'current' => $page == $currpage];
        }

        return $this->render('Reviews/reviews.html.twig', ['pagetitle' => $pagetitle, 'pagedescription' => 'Read the latest user-submitted reviews on the cards.', 'reviews' => $reviews, 'url' => $request->getRequestUri(), 'route' => $route, 'pages' => $pages, 'prevurl' => 1 == $currpage ? null : $this->generateUrl($route, $params + ['page' => $prevpage]), 'nexturl' => $currpage == $nbpages ? null : $this->generateUrl($route, $params + ['page' => $nextpage])], $response);
    }
}
