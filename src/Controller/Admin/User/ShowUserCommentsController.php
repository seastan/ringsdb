<?php

declare(strict_types=1);

namespace App\Controller\Admin\User;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ShowUserCommentsController extends AbstractController
{
    public function __construct(
        private readonly UserRepository $userRepository
    ) {
    }

    #[Route(path: '/admin/user/comments/{user_id}', name: 'admin_user_comments_show', methods: ['GET'])]
    public function __invoke(int $user_id): Response
    {
        /* @var $user User */
        $user = $this->userRepository->find($user_id);
        if (!$user instanceof User) {
            throw $this->createNotFoundException('User not found');
        }

        return $this->render('Admin/user_comments.html.twig', ['pagetitle' => 'User Admin', 'user' => $user]);
    }
}
