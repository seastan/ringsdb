<?php

declare(strict_types=1);

namespace App\Controller\API;

use App\Entity\Deck;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * External-integration deck endpoint (used by the DragnCards "Play on DragnCards"
 * links for private decks). Despite the legacy "oauth2" path it uses no OAuth: it is
 * anonymous (security.yaml) and CORS-open, and only returns a deck when its owner has enabled
 * "Share my decks". Published decklists go through GetDecklistController instead.
 */
class LoadSharedDeckController extends AbstractController
{
    /**
     * Return one Deck as JSON, if the owner shares their decks.
     */
    #[Route(path: '/api/oauth2/deck/load/{id}', name: 'api_oauth2_load_deck', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function __invoke(?Deck $deck): Response
    {
        $response = new Response();
        $response->headers->set('Content-Type', 'application/json');
        $response->headers->add(['Access-Control-Allow-Origin' => '*']);

        if (!$deck instanceof Deck) {
            $response->setContent((string) json_encode([
                'success' => false,
                'error' => 'Deck not found.',
            ]));

            return $response;
        }

        $user = $deck->getUser();
        if (!$user->getIsShareDecks()) {
            $response->setContent((string) json_encode([
                'success' => false,
                'error' => 'You are not allowed to view this deck. To get access, you can ask the deck owner to enable "Share my decks" on their account.',
            ]));

            return $response;
        }

        $response->setContent((string) json_encode($deck));

        return $response;
    }
}
