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

class EditDeckController extends AbstractController
{
    use CurrentUserTrait;

    private DeckRepository $deckRepository;

    public function __construct(
        DeckRepository $deckRepository
    ) {
        $this->deckRepository = $deckRepository;
    }

    /**
     * @Route("/deck/edit/{deck_id}", name="deck_edit", methods={"GET"}, requirements={"deck_id"="\d+"})
     */
    public function __invoke(int $deck_id): Response
    {
        /* @var $deck \App\Entity\Deck */
        $deck = $this->deckRepository->find($deck_id);
        if (!$deck) {
            throw new NotFoundHttpException("This deck doesn't exist.");
        }

        if ($this->currentUser()->getId() != $deck->getUser()->getId()) {
            throw new AccessDeniedHttpException('You are not allowed to view this deck.');
        }

        return $this->render('Builder/deckedit.html.twig', ['pagetitle' => 'Deckbuilder', 'deck' => $deck]);
    }
}
