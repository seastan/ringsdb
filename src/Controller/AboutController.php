<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class AboutController extends AbstractController
{
    public function __construct(
        private readonly int $cacheExpiration,
        private readonly ?string $gameName
    ) {
    }

    #[Route(path: '/about', name: 'about')]
    public function __invoke(): Response
    {
        $response = new Response();
        $response->setPublic();
        $response->setMaxAge($this->cacheExpiration);

        return $this->render('Default/about.html.twig', [
            'pagetitle' => 'About',
            'game_name' => $this->gameName,
        ], $response);
    }
}
