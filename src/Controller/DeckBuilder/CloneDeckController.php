<?php

declare(strict_types=1);

namespace App\Controller\DeckBuilder;

use App\Repository\DeckRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

class CloneDeckController extends AbstractController
{
    private DeckRepository $deckRepository;

    public function __construct(DeckRepository $deckRepository)
    {
        $this->deckRepository = $deckRepository;
    }

    /**
     * @Route("/deck/clone/{deck_id}", name="deck_clone", methods={"GET"}, requirements={"deck_id"="\d+"})
     */
    public function __invoke(int $deck_id): Response
    {
        /* @var $deck \App\Entity\Deck */
        $deck = $this->deckRepository->find($deck_id);
        if (!$deck) {
            throw new NotFoundHttpException("This deck doesn't exist.");
        }

        $is_owner = $this->getUser() && $this->getUser()->getId() == $deck->getUser()->getId();
        if (!$deck->getUser()->getIsShareDecks() && !$is_owner) {
            throw new AccessDeniedHttpException('You are not allowed to view this deck. To get access, you can ask the deck owner to enable "Share my decks" on their account.');
        }

        $content = ['main' => [], 'side' => []];
        foreach ($deck->getSlots() as $slot) {
            $content['main'][$slot->getCard()->getCode()] = $slot->getQuantity();
        }

        foreach ($deck->getSideslots() as $slot) {
            $content['side'][$slot->getCard()->getCode()] = $slot->getQuantity();
        }

        return $this->forward(SaveDeckController::class, ['name' => $deck->getName().' (clone)', 'content' => json_encode($content), 'decklist_id' => $deck->getParent() ? $deck->getParent()->getId() : null]);
    }
}
