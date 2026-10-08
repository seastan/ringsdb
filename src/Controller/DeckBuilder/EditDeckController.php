<?php

declare(strict_types=1);

namespace App\Controller\DeckBuilder;

use App\Controller\CurrentUserTrait;
use App\Repository\DeckRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

class EditDeckController extends AbstractController
{
    use CurrentUserTrait;

    public function __construct(
        private DeckRepository $deckRepository
    ) {
    }

    #[Route(path: '/deck/edit/{deck_id}', name: 'deck_edit', requirements: ['deck_id' => '\d+'], methods: ['GET'])]
    public function __invoke(int $deck_id): Response
    {
        /* @var $deck \App\Entity\Deck */
        $deck = $this->deckRepository->find($deck_id);
        if (!$deck instanceof \App\Entity\Deck) {
            throw new NotFoundHttpException("This deck doesn't exist.");
        }

        if ($this->currentUser()->getId() != $deck->getUser()->getId()) {
            throw new AccessDeniedHttpException('You are not allowed to view this deck.');
        }

        return $this->render('Builder/deckedit.html.twig', ['pagetitle' => 'Deckbuilder', 'deck' => $deck]);
    }
}
