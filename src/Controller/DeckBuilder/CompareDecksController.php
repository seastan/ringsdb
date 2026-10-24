<?php

declare(strict_types=1);

namespace App\Controller\DeckBuilder;

use App\Repository\DeckRepository;
use App\Services\Diff;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

class CompareDecksController extends AbstractController
{
    public function __construct(
        private readonly DeckRepository $deckRepository,
        private readonly Diff $diffService
    ) {
    }

    #[Route(path: '/deck/compare/{deck1_id}/{deck2_id}', name: 'decks_diff', requirements: ['deck1_id' => '\d+', 'deck2_id' => '\d+'], methods: ['GET'])]
    public function __invoke(int $deck1_id, int $deck2_id): Response
    {
        /* @var $deck1 \App\Entity\Deck */
        $deck1 = $this->deckRepository->find($deck1_id);
        /* @var $deck2 \App\Entity\Deck */
        $deck2 = $this->deckRepository->find($deck2_id);
        if (!$deck1 || !$deck2) {
            throw new NotFoundHttpException("This deck doesn't exist.");
        }

        $is_owner = $this->getUser() instanceof \Symfony\Component\Security\Core\User\UserInterface && $this->getUser()->getId() == $deck1->getUser()->getId();
        if (!$deck1->getUser()->getIsShareDecks() && !$is_owner) {
            throw new AccessDeniedHttpException('You are not allowed to view this deck. To get access, you can ask the deck owner to enable "Share my decks" on their account.');
        }

        $is_owner = $this->getUser() instanceof \Symfony\Component\Security\Core\User\UserInterface && $this->getUser()->getId() == $deck2->getUser()->getId();
        if (!$deck2->getUser()->getIsShareDecks() && !$is_owner) {
            throw new AccessDeniedHttpException('You are not allowed to view this deck. To get access, you can ask the deck owner to enable "Share my decks" on their account.');
        }

        $heroDeck = $this->diffService->compareSlots([
            $deck1->getSlots()->getHeroDeck(),
            $deck2->getSlots()->getHeroDeck(),
        ]);

        $drawDeck = $this->diffService->compareSlots([
            $deck1->getSlots()->getDrawDeck(),
            $deck2->getSlots()->getDrawDeck(),
        ]);

        $sideboard = $this->diffService->compareSlots([
            $deck1->getSideSlots(),
            $deck2->getSideSlots(),
        ]);

        return $this->render(
            'Compare/deck_compare.html.twig',
            [
                'deck1' => $deck1,
                'deck2' => $deck2,
                'hero_deck' => $heroDeck,
                'draw_deck' => $drawDeck,
                'sideboard' => $sideboard,
            ]
        );
    }
}
