<?php

declare(strict_types=1);

namespace App\Controller\DeckBuilder;

use App\Controller\CurrentUserTrait;
use App\Entity\Decklist;
use App\Services\DeckSaver;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class CopyDeckController extends AbstractController
{
    use CurrentUserTrait;

    public function __construct(
        private readonly DeckSaver $deckSaver
    ) {
    }

    #[Route(path: '/deck/copy/{decklist_id}', name: 'deck_copy', requirements: ['decklist_id' => '\d+'])]
    public function __invoke(#[MapEntity(id: 'decklist_id', message: "This deck doesn't exist.")] Decklist $decklist): Response
    {
        $content = ['main' => [], 'side' => []];
        foreach ($decklist->getSlots() as $slot) {
            $content['main'][$slot->getCard()->getCode()] = $slot->getQuantity();
        }

        foreach ($decklist->getSideslots() as $slot) {
            $content['side'][$slot->getCard()->getCode()] = $slot->getQuantity();
        }

        $this->deckSaver->save(
            $this->currentUser(),
            $content,
            $decklist->getName(),
            decklist: $decklist
        );

        return $this->redirectToRoute('decks_list');
    }
}
