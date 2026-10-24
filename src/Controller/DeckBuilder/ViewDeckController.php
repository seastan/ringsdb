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

class ViewDeckController extends AbstractController
{
    use CurrentUserTrait;

    #[Route(path: '/deck/view/{deck_id}', name: 'deck_view', requirements: ['deck_id' => '\d+'], defaults: ['deck_id' => 0], methods: ['GET'])]
    public function __invoke(#[MapEntity(id: 'deck_id', message: "This deck doesn't exist.")] Deck $deck): Response
    {
        $is_owner = $this->getUser() instanceof \Symfony\Component\Security\Core\User\UserInterface && $this->getUser()->getId() == $deck->getUser()->getId();
        if (!$deck->getUser()->getIsShareDecks() && !$is_owner) {
            throw new AccessDeniedHttpException('You are not allowed to view this deck. To get access, you can ask the deck owner to enable "Share my decks" on their account.');
        }

        return $this->render('Builder/deckview.html.twig', ['pagetitle' => 'Deckbuilder', 'deck' => $deck, 'deck_id' => $deck->getId(), 'is_owner' => $is_owner]);
    }
}
