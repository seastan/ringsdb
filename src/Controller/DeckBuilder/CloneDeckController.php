<?php

declare(strict_types=1);

namespace App\Controller\DeckBuilder;

use App\Controller\CurrentUserTrait;
use App\Entity\Deck;
use App\Repository\DeckRepository;
use App\Services\DeckSaver;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

class CloneDeckController extends AbstractController
{
    use CurrentUserTrait;

    public function __construct(
        private readonly DeckRepository $deckRepository,
        private readonly DeckSaver $deckSaver
    ) {
    }

    #[Route(path: '/deck/clone/{deck_id}', name: 'deck_clone', requirements: ['deck_id' => '\d+'], methods: ['GET'])]
    public function __invoke(int $deck_id): Response
    {
        $user = $this->currentUser();

        $deck = $this->deckRepository->find($deck_id);
        if (!$deck instanceof Deck) {
            throw new NotFoundHttpException("This deck doesn't exist.");
        }

        $is_owner = $deck->getUser()->isEqualTo($user);
        if (!$deck->getUser()->getIsShareDecks() && !$is_owner) {
            throw new AccessDeniedHttpException('You are not allowed to view this deck. To get access, you can ask the deck owner to enable "Share my decks" on their account.');
        }

        $this->deckSaver->cloneDeck($user, $deck);

        return $this->redirectToRoute('decks_list');
    }
}
