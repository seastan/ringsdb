<?php

declare(strict_types=1);

namespace App\Controller\Fellowship;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Attribute\Route;

class ByAuthorFellowshipController extends AbstractController
{
    #[Route(path: '/f/{username}', name: 'fellowship_byauthor', methods: ['GET'])]
    public function __invoke(string $username): RedirectResponse
    {
        return $this->redirect($this->generateUrl('fellowships_list', ['type' => 'find', 'author' => $username]));
    }
}
