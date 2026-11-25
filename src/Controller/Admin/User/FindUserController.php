<?php

declare(strict_types=1);

namespace App\Controller\Admin\User;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class FindUserController extends AbstractController
{
    #[Route(path: '/admin/user/find', name: 'admin_find_user', methods: ['GET'])]
    public function __invoke(): Response
    {
        return $this->render('Admin/find_user.html.twig', ['pagetitle' => 'Admin']);
    }
}
