<?php

declare(strict_types=1);

namespace App\Controller\DeckBuilder;

use App\Controller\CurrentUserTrait;
use App\Entity\Deck;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Attribute\Route;

class EditDeckController extends AbstractController
{
    use CurrentUserTrait;

    #[Route(path: '/deck/edit/{deck_id}', name: 'deck_edit', requirements: ['deck_id' => '\d+'], methods: ['GET'])]
    public function __invoke(#[MapEntity(id: 'deck_id', message: "This deck doesn't exist.")] Deck $deck): Response
    {
        if ($this->currentUser()->getId() != $deck->getUser()->getId()) {
            throw new AccessDeniedHttpException('You are not allowed to view this deck.');
        }

        return $this->render('Builder/deckedit.html.twig', ['pagetitle' => 'Deckbuilder', 'deck' => $deck]);
    }
}
