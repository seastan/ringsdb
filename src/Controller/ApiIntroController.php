<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ApiIntroController extends AbstractController
{
    public function __construct(
        private readonly int $cacheExpiration,
        private readonly ?string $gameName,
        private readonly ?string $publisherName
    ) {
    }

    #[Route(path: '/api/', name: 'api_intro')]
    public function __invoke(): Response
    {
        $response = new Response();
        $response->setPublic();
        $response->setMaxAge($this->cacheExpiration);

        return $this->render('Default/apiIntro.html.twig', [
            'pagetitle' => 'API',
            'game_name' => $this->gameName,
            'publisher_name' => $this->publisherName,
        ], $response);
    }
}
