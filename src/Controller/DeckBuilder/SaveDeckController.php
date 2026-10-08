<?php

declare(strict_types=1);

namespace App\Controller\DeckBuilder;

use App\Controller\CurrentUserTrait;
use App\Repository\DeckRepository;
use App\Services\Decks;
use App\Services\DeckSaver;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Attribute\Route;

class SaveDeckController extends AbstractController
{
    use CurrentUserTrait;

    public function __construct(
        private readonly DeckRepository $deckRepository,
        private readonly Decks $decks,
        private readonly DeckSaver $deckSaver
    ) {
    }

    #[Route(path: '/deck/save', name: 'deck_save', methods: ['POST'])]
    public function __invoke(Request $request): Response
    {
        $user = $this->currentUser();

        $id = filter_var($request->request->get('id'), FILTER_SANITIZE_NUMBER_INT);
        $deck = null;
        if ($id) {
            /* @var $deck \App\Entity\Deck */
            $deck = $this->deckRepository->find($id);
            if (!$deck || !$deck->getUser()->isEqualTo($user)) {
                throw new AccessDeniedHttpException("You don't have access to this deck.");
            }
        }

        $cancel_edits = (bool) filter_var($request->request->get('cancel_edits'), FILTER_SANITIZE_NUMBER_INT);
        if ($cancel_edits) {
            if ($deck instanceof \App\Entity\Deck) {
                $this->decks->revertDeck($deck);
            }

            return $this->redirect($this->generateUrl('decks_list'));
        }

        $content = json_decode($request->request->getString('content'), true);
        if (!isset($content['main']) || !is_array($content['main'])) {
            return new Response('Cannot import an empty deck');
        }

        // a copy is a new deck, its changes computed from the copied deck
        $is_copy = (bool) filter_var($request->request->get('copy'), FILTER_SANITIZE_NUMBER_INT);
        $this->deckSaver->save(
            $user,
            $content,
            $request->request->getString('name'),
            $request->request->getString('description'),
            $request->request->getString('tags'),
            deck: $is_copy ? null : $deck,
            sourceDeck: $deck,
        );

        return $this->redirect($this->generateUrl('decks_list'));
    }
}
