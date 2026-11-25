<?php

declare(strict_types=1);

namespace App\Controller\API;

use App\Entity\Deck;
use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class LoadDeckController extends AbstractController
{
    /*
     * Get the description of one Deck of the authenticated user
     */
    #[Route(path: '/api/private/deck/load/{id}', name: 'api_private_load_deck', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function __invoke(Request $request, ?Deck $deck): Response
    {
        if (!$deck instanceof Deck) {
            return new JsonResponse(['success' => false, 'error' => 'This deck does not exists.']);
        }

        /* @var $user User */
        $user = $deck->getUser();
        if (!$user->getIsShareDecks() && $user != $this->getUser()) {
            return new JsonResponse(['success' => false, 'error' => 'You are not allowed to view this deck. To get access, you can ask the deck owner to enable "Share my decks" on their account.']);
        }

        $response = new JsonResponse();

        $response->setLastModified($deck->getDateUpdate());
        if ($response->isNotModified($request)) {
            return $response;
        }

        $response->setData($deck);

        return $response;
    }
}
