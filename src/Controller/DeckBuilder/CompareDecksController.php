<?php

declare(strict_types=1);

namespace App\Controller\DeckBuilder;

use App\Repository\DeckRepository;
use App\Services\Diff;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

class CompareDecksController extends AbstractController
{
    private DeckRepository $deckRepository;

    public function __construct(
        DeckRepository $deckRepository
    ) {
        $this->deckRepository = $deckRepository;
    }

    /**
     * @Route(
     *     "/deck/compare/{deck1_id}/{deck2_id}",
     *     name="decks_diff",
     *     methods={"GET"},
     *     requirements={"deck1_id"="\d+", "deck2_id"="\d+"}
     * )
     */
    public function compareAction($deck1_id, $deck2_id, Diff $diffService): Response
    {
        /* @var $deck1 \App\Entity\Deck */
        $deck1 = $this->deckRepository->find($deck1_id);
        /* @var $deck2 \App\Entity\Deck */
        $deck2 = $this->deckRepository->find($deck2_id);
        if (!$deck1 || !$deck2) {
            throw new NotFoundHttpException("This deck doesn't exist.");
        }
        $is_owner = $this->getUser() && $this->getUser()->getId() == $deck1->getUser()->getId();
        if (!$deck1->getUser()->getIsShareDecks() && !$is_owner) {
            throw new AccessDeniedHttpException('You are not allowed to view this deck. To get access, you can ask the deck owner to enable "Share my decks" on their account.');
        }
        $is_owner = $this->getUser() && $this->getUser()->getId() == $deck2->getUser()->getId();
        if (!$deck2->getUser()->getIsShareDecks() && !$is_owner) {
            throw new AccessDeniedHttpException('You are not allowed to view this deck. To get access, you can ask the deck owner to enable "Share my decks" on their account.');
        }

        return $this->render('Compare/deck_compare.html.twig', ['deck1' => $deck1, 'deck2' => $deck2, 'hero_deck' => $diffService->compareSlots([$deck1->getSlots()->getHeroDeck(), $deck2->getSlots()->getHeroDeck()]), 'draw_deck' => $diffService->compareSlots([$deck1->getSlots()->getDrawDeck(), $deck2->getSlots()->getDrawDeck()]), 'sideboard' => $diffService->compareSlots([$deck1->getSideSlots(), $deck2->getSideSlots()])]);
    }
}
