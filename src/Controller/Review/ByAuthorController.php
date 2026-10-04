<?php

namespace App\Controller\Review;

use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ByAuthorController extends AbstractController
{
    private int $cacheExpiration;
    private UserRepository $userRepository;
    private EntityManagerInterface $entityManager;

    public function __construct(
        int $cacheExpiration,
        UserRepository $userRepository,
        EntityManagerInterface $entityManager
    ) {
        $this->cacheExpiration = $cacheExpiration;
        $this->userRepository = $userRepository;
        $this->entityManager = $entityManager;
    }

    /**
     * @Route(
     *     "/user/reviews/{user_id}/{page}",
     *     name="card_reviews_list_byauthor",
     *     requirements={"page"="\d+", "user_id"="\d+"},
     *     defaults={"page"=1}
     * )
     */
    public function byauthorAction(Request $request, int $user_id, int $page = 1): Response
    {
        $response = new Response();
        $response->setPublic();
        $response->setMaxAge($this->cacheExpiration);
        $limit = 5;
        if ($page < 1) {
            $page = 1;
        }
        $start = ($page - 1) * $limit;
        $user = $this->userRepository->find($user_id);
        if (!$user) {
            throw $this->createNotFoundException('User not found.');
        }
        $pagetitle = 'Card Reviews by '.$user->getUsername();
        $dql = 'SELECT r FROM App:Review r WHERE r.user=:USER ORDER BY r.dateCreation DESC, r.id DESC';
        $query = $this->entityManager->createQuery($dql)->setFirstResult($start)->setMaxResults($limit)->setParameter('USER', $user);
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
            $pages[] = ['numero' => $page, 'url' => $this->generateUrl($route, $params + ['user_id' => $user_id, 'page' => $page]), 'current' => $page == $currpage];
        }

        return $this->render('Reviews/reviews.html.twig', ['pagetitle' => $pagetitle, 'pagedescription' => 'Read the latest user-submitted reviews on the cards.', 'reviews' => $reviews, 'url' => $request->getRequestUri(), 'route' => $route, 'pages' => $pages, 'prevurl' => 1 == $currpage ? null : $this->generateUrl($route, $params + ['user_id' => $user_id, 'page' => $prevpage]), 'nexturl' => $currpage == $nbpages ? null : $this->generateUrl($route, $params + ['user_id' => $user_id, 'page' => $nextpage])], $response);
    }
}
