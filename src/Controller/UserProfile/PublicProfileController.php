<?php

declare(strict_types=1);

namespace App\Controller\UserProfile;

use App\Entity\User;
use Doctrine\ORM\EntityManager;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class PublicProfileController extends AbstractController
{
    public function __construct(
        private readonly int $cacheExpiration
    ) {
    }

    /**
     * displays details about a user and the list of decklists he published.
     */
    #[Route(path: '/user/profile/{user_id}/{user_name}/{page}', name: 'user_profile_public', requirements: ['user_id' => '\d+', 'page' => '\d+'], defaults: ['page' => 1], methods: ['GET'])]
    public function __invoke(#[MapEntity(id: 'user_id', message: 'No such user.')] User $user, string $user_name, int $page): Response
    {
        $response = new Response();
        $response->setPublic();
        $response->setMaxAge($this->cacheExpiration);

        /* @var $em EntityManager */
        return $this->render('User/profile_public.html.twig', ['user' => $user]);
    }
}
