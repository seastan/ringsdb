<?php

declare(strict_types=1);

namespace App\Controller\API;

use App\Repository\DeckRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * External-integration deck endpoint (used by the DragnCards "Play on DragnCards"
 * links for private decks). Despite the legacy "oauth2" path it uses no OAuth: it is
 * anonymous (security.yaml) and CORS-open, and only returns a deck when its owner has enabled
 * "Share my decks". Published decklists go through ApiController instead.
 */
class LoadSharedDeckController extends AbstractController
{
    private DeckRepository $deckRepository;

    public function __construct(DeckRepository $deckRepository)
    {
        $this->deckRepository = $deckRepository;
    }

    /**
     * Return one Deck as JSON, if the owner shares their decks.
     *
     * @Route("/api/oauth2/deck/load/{id}", name="api_oauth2_load_deck", methods={"GET"}, requirements={"id"="\d+"})
     */
    public function __invoke(int $id): Response
    {
        $response = new Response();
        $response->headers->set('Content-Type', 'application/json');
        $response->headers->add(['Access-Control-Allow-Origin' => '*']);

        $deck = $this->deckRepository->find($id);

        if (!$deck) {
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
