<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class AdminController extends AbstractController
{
    #[Route(path: '/admin/', name: 'admin', methods: ['GET'])]
    public function __invoke(): Response
    {
        return $this->render('Admin/index.html.twig');
    }
}
