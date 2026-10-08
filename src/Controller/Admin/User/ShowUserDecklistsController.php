<?php

declare(strict_types=1);

namespace App\Controller\Admin\User;

use App\Entity\User;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ShowUserDecklistsController extends AbstractController
{
    #[Route(path: '/admin/user/decklists/{user_id}', name: 'admin_user_decklists_show', methods: ['GET'])]
    public function __invoke(#[MapEntity(id: 'user_id', message: 'User not found')] User $user): Response
    {
        return $this->render('Admin/user_decklists.html.twig', ['pagetitle' => 'User Admin', 'user' => $user]);
    }
}
