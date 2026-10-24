<?php

declare(strict_types=1);

namespace App\Controller\Admin\User;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Attribute\Route;

class ToggleUserLockedController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    #[Route(path: '/admin/user/toggle_locked/{user_id}', name: 'admin_user_locked_toggle', methods: ['GET'])]
    public function __invoke(#[MapEntity(id: 'user_id', message: 'User not found')] User $user): RedirectResponse
    {
        $user->setLocked(!$user->isLocked());
        $this->entityManager->flush();

        return $this->redirect($this->generateUrl('admin_show_user', ['user_id' => $user->getId()]));
    }
}
