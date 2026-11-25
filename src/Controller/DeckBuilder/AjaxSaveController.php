<?php

declare(strict_types=1);

namespace App\Controller\DeckBuilder;

use App\Controller\CurrentUserTrait;
use App\Exception\TooManyDecksException;
use App\Model\SaveDeckDto;
use App\Repository\DeckRepository;
use App\Services\DeckSaver;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

class AjaxSaveController extends AbstractController
{
    use CurrentUserTrait;

    public function __construct(
        private readonly DeckRepository $deckRepository,
        private readonly DeckSaver $deckSaver
    ) {
    }

    #[Route(path: '/deck/save-ajax', name: 'deck_save_ajax', methods: ['POST'])]
    public function __invoke(#[MapRequestPayload] SaveDeckDto $payload = new SaveDeckDto()): JsonResponse
    {
        $user = $this->currentUser();

        $id = filter_var($payload->id, FILTER_SANITIZE_NUMBER_INT);
        $deck = null;
        if ($id) {
            /* @var $deck \App\Entity\Deck */
            $deck = $this->deckRepository->find($id);
            if (!$deck || !$deck->getUser()->isEqualTo($user)) {
                return new JsonResponse(['success' => false, 'error' => "You don't have access to this deck."], 403);
            }
        }

        $content = json_decode($payload->content, true);
        if (!isset($content['main']) || empty($content['main'])) {
            return new JsonResponse(['success' => false, 'error' => 'Cannot save an empty deck.'], 422);
        }

        try {
            $deck = $this->deckSaver->save(
                $user,
                $content,
                $payload->name,
                $payload->description,
                $payload->tags,
                deck: $deck,
                sourceDeck: $deck,
            );
        } catch (TooManyDecksException) {
            return new JsonResponse(['success' => false, 'error' => 'You have reached the maximum number of decks allowed.'], 422);
        }

        return new JsonResponse(['success' => true, 'id' => $deck->getId()]);
    }
}
