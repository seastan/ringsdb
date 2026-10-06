<?php

declare(strict_types=1);

namespace App\Controller\DeckBuilder;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ImportDeckController extends AbstractController
{
    public function __construct(private int $cacheExpiration)
    {
    }

    /**
     * @Route("/deck/import", name="deck_import", methods={"GET"})
     */
    public function __invoke(): Response
    {
        $response = new Response();
        $response->setPublic();
        $response->setMaxAge($this->cacheExpiration);

        return $this->render('Builder/directimport.html.twig', ['pagetitle' => 'Import a deck'], $response);
    }
}
