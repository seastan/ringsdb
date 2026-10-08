<?php

declare(strict_types=1);

namespace App\Controller\Admin\User;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Attribute\Route;

class ToggleUserLockedController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserRepository $userRepository
    ) {
    }

    #[Route(path: '/admin/user/toggle_locked/{user_id}', name: 'admin_user_locked_toggle', methods: ['GET'])]
    public function __invoke(int $user_id): RedirectResponse
    {
        /* @var $user User */
        $user = $this->userRepository->find($user_id);
        if (!$user instanceof User) {
            throw $this->createNotFoundException('User not found');
        }

        $user->setLocked(!$user->isLocked());
        $this->entityManager->flush();

        return $this->redirect($this->generateUrl('admin_show_user', ['user_id' => $user->getId()]));
    }
}
