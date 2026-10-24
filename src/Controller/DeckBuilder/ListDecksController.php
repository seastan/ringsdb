<?php

declare(strict_types=1);

namespace App\Controller\DeckBuilder;

use App\Controller\CurrentUserTrait;
use App\Entity\User;
use App\Services\Decks;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ListDecksController extends AbstractController
{
    use CurrentUserTrait;

    public function __construct(
        private Decks $decks
    ) {
    }

    #[Route(path: '/decks', name: 'decks_list', methods: ['GET'])]
    public function __invoke(Request $request): Response
    {
        /* @var $user User */
        $user = $this->currentUser();
        $decksService = $this->decks;
        $showAll = (bool) $request->query->get('all', false);
        $limit = $showAll ? null : 10;
        $totalDecks = $decksService->countDecksForUser($user);
        if (0 === $totalDecks) {
            return $this->render('Builder/no-decks.html.twig', ['pagetitle' => 'My Decks', 'pagedescription' => 'Create custom decks with the help of a powerful deckbuilder.', 'nbmax' => $user->getMaxNbDecks()]);
        }

        // The service returns lightweight per-deck arrays (id/name/version/problem/tags/
        // last_pack/slots/heroes) already shaped for the template — no entity hydration.
        $decks = $decksService->getDecksWithSlotsForUser($user, $limit);
        $tags = [];
        foreach ($decks as $deck) {
            $tags[] = $deck['tags'];
        }

        $tags = array_unique($tags);

        return $this->render('Builder/decks.html.twig', ['pagetitle' => 'My Decks', 'pagedescription' => 'Create custom decks with the help of a powerful deckbuilder.', 'decks' => $decks, 'tags' => $tags, 'nbmax' => $user->getMaxNbDecks(), 'nbdecks' => $totalDecks, 'nbloaded' => count($decks), 'cannotcreate' => $user->getMaxNbDecks() <= $totalDecks, 'show_all' => $showAll]);
    }
}
