<?php

declare(strict_types=1);

namespace App\Controller\DeckBuilder;

use App\Controller\CurrentUserTrait;
use App\Exception\TooManyDecksException;
use App\Repository\DeckRepository;
use App\Services\DeckSaver;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
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
    public function __invoke(Request $request): JsonResponse
    {
        $user = $this->currentUser();

        $id = filter_var($request->request->get('id'), FILTER_SANITIZE_NUMBER_INT);
        $deck = null;
        if ($id) {
            /* @var $deck \App\Entity\Deck */
            $deck = $this->deckRepository->find($id);
            if (!$deck || !$deck->getUser()->isEqualTo($user)) {
                return new JsonResponse(['success' => false, 'error' => "You don't have access to this deck."], 403);
            }
        }

        $content = json_decode($request->request->getString('content'), true);
        if (!isset($content['main']) || empty($content['main'])) {
            return new JsonResponse(['success' => false, 'error' => 'Cannot save an empty deck.'], 422);
        }

        $decklist_id = filter_var($request->request->get('decklist_id'), FILTER_SANITIZE_NUMBER_INT);
        if (false === $decklist_id) {
            throw new BadRequestHttpException('Wrong decklist_id');
        }

        try {
            $deck = $this->deckSaver->save(
                $user,
                $deck,
                $deck,
                $content,
                $request->request->getString('name'),
                $request->request->getString('description'),
                $request->request->getString('tags'),
                (int) $decklist_id
            );
        } catch (TooManyDecksException) {
            return new JsonResponse(['success' => false, 'error' => 'You have reached the maximum number of decks allowed.'], 422);
        }

        return new JsonResponse(['success' => true, 'id' => $deck->getId()]);
    }
}
