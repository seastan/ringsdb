<?php

declare(strict_types=1);

namespace App\Controller\DeckBuilder;

use App\Controller\CurrentUserTrait;
use App\Repository\DeckRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

class ViewDeckController extends AbstractController
{
    use CurrentUserTrait;

    private DeckRepository $deckRepository;

    public function __construct(
        DeckRepository $deckRepository
    ) {
        $this->deckRepository = $deckRepository;
    }

    /**
     * @Route(
     *     "/deck/view/{deck_id}",
     *     name="deck_view",
     *     methods={"GET"},
     *     requirements={"deck_id"="\d+"},
     *     defaults={"deck_id"=0}
     * )
     */
    public function __invoke($deck_id): Response
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

        return $this->render('Builder/deckview.html.twig', ['pagetitle' => 'Deckbuilder', 'deck' => $deck, 'deck_id' => $deck_id, 'is_owner' => $is_owner]);
    }
}
