<?php

declare(strict_types=1);

namespace App\Controller\UserProfile;

use App\Repository\UserRepository;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

class PublicProfileController extends AbstractController
{
    public function __construct(private int $cacheExpiration, private UserRepository $userRepository)
    {
    }

    /**
     * displays details about a user and the list of decklists he published.
     *
     * @Route(
     *     "/user/profile/{user_id}/{user_name}/{page}",
     *     name="user_profile_public",
     *     methods={"GET"},
     *     requirements={"user_id"="\d+","page"="\d+"},
     *     defaults={"page"=1}
     * )
     */
    public function __invoke(int $user_id, string $user_name, int $page): Response
    {
        $response = new Response();
        $response->setPublic();
        $response->setMaxAge($this->cacheExpiration);
        /* @var $em EntityManager */
        /* @var $user \App\Entity\User */
        $user = $this->userRepository->find($user_id);
        if (!$user) {
            throw new NotFoundHttpException('No such user.');
        }

        return $this->render('User/profile_public.html.twig', ['user' => $user]);
    }
}
