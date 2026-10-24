<?php

declare(strict_types=1);

namespace App\Controller\DeckBuilder;

use App\Entity\Decklist;
use App\Repository\DecklistRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

class CopyDeckController extends AbstractController
{
    public function __construct(
        private readonly DecklistRepository $decklistRepository
    ) {
    }

    #[Route(path: '/deck/copy/{decklist_id}', name: 'deck_copy', requirements: ['decklist_id' => '\d+'])]
    public function __invoke(int $decklist_id): Response
    {
        /* @var $decklist Decklist */
        $decklist = $this->decklistRepository->find($decklist_id);
        if (!$decklist) {
            throw new NotFoundHttpException("This deck doesn't exist.");
        }

        $content = ['main' => [], 'side' => []];
        foreach ($decklist->getSlots() as $slot) {
            $content['main'][$slot->getCard()->getCode()] = $slot->getQuantity();
        }

        foreach ($decklist->getSideslots() as $slot) {
            $content['side'][$slot->getCard()->getCode()] = $slot->getQuantity();
        }

        return $this->forward(SaveDeckController::class, ['name' => $decklist->getName(), 'content' => json_encode($content), 'decklist_id' => $decklist_id]);
    }
}
