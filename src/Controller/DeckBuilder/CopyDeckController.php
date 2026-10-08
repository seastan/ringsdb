<?php

declare(strict_types=1);

namespace App\Controller\DeckBuilder;

use App\Controller\CurrentUserTrait;
use App\Entity\Decklist;
use App\Repository\DecklistRepository;
use App\Services\DeckSaver;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

class CopyDeckController extends AbstractController
{
    use CurrentUserTrait;

    public function __construct(
        private readonly DecklistRepository $decklistRepository,
        private readonly DeckSaver $deckSaver
    ) {
    }

    #[Route(path: '/deck/copy/{decklist_id}', name: 'deck_copy', requirements: ['decklist_id' => '\d+'])]
    public function __invoke(int $decklist_id): Response
    {
        /* @var $decklist Decklist */
        $decklist = $this->decklistRepository->find($decklist_id);
        if (!$decklist instanceof Decklist) {
            throw new NotFoundHttpException("This deck doesn't exist.");
        }

        $content = ['main' => [], 'side' => []];
        foreach ($decklist->getSlots() as $slot) {
            $content['main'][$slot->getCard()->getCode()] = $slot->getQuantity();
        }

        foreach ($decklist->getSideslots() as $slot) {
            $content['side'][$slot->getCard()->getCode()] = $slot->getQuantity();
        }

        $this->deckSaver->save($this->currentUser(), null, null, $content, $decklist->getName(), decklistId: $decklist_id);

        return $this->redirectToRoute('decks_list');
    }
}
