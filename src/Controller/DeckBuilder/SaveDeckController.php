<?php

declare(strict_types=1);

namespace App\Controller\DeckBuilder;

use App\Controller\CurrentUserTrait;
use App\Entity\Deck;
use App\Entity\User;
use App\Repository\DeckRepository;
use App\Services\Decks;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\Routing\Annotation\Route;

class SaveDeckController extends AbstractController
{
    use CurrentUserTrait;

    private DeckRepository $deckRepository;
    private EntityManagerInterface $entityManager;
    private Decks $decks;

    public function __construct(
        EntityManagerInterface $entityManager,
        DeckRepository $deckRepository,
        Decks $decks
    ) {
        $this->deckRepository = $deckRepository;
        $this->entityManager = $entityManager;
        $this->decks = $decks;
    }

    /**
     * @Route("/deck/save", name="deck_save", methods={"POST"})
     */
    public function __invoke(Request $request): Response
    {
        /* @var $user User */
        $user = $this->currentUser();
        if (count($user->getDecks()) > $user->getMaxNbDecks()) {
            throw new UnprocessableEntityHttpException('You have reached the maximum number of decks allowed. Delete some decks or increase your reputation.');
        }
        $id = filter_var($request->get('id'), FILTER_SANITIZE_NUMBER_INT);
        $deck = null;
        $source_deck = null;
        if ($id) {
            /* @var $deck \App\Entity\Deck */
            $deck = $this->deckRepository->find($id);
            if (!$deck || $user->getId() != $deck->getUser()->getId()) {
                throw new AccessDeniedHttpException("You don't have access to this deck.");
            }
            $source_deck = $deck;
        }
        $cancel_edits = (bool) filter_var($request->get('cancel_edits'), FILTER_SANITIZE_NUMBER_INT);
        if ($cancel_edits) {
            if ($deck) {
                $this->decks->revertDeck($deck);
            }

            return $this->redirect($this->generateUrl('decks_list'));
        }
        $is_copy = (bool) filter_var($request->get('copy'), FILTER_SANITIZE_NUMBER_INT);
        if ($is_copy || !$id) {
            /* @var $deck \App\Entity\Deck */
            $deck = new Deck();
        }
        $content = json_decode($request->get('content'), true);
        if (!isset($content['main']) || !is_array($content['main'])) {
            return new Response('Cannot import an empty deck');
        }
        $name = filter_var($request->get('name'), FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES);
        if (empty($name)) {
            $name = 'Untitled Deck';
        }
        $decklist_id = filter_var($request->get('decklist_id'), FILTER_SANITIZE_NUMBER_INT);
        if (false === $decklist_id) {
            throw new BadRequestHttpException('Wrong decklist_id');
        }
        $description = trim($request->get('description') ?? '');
        $tags = filter_var($request->get('tags'), FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES) ?: '';
        $this->decks->saveDeck($user, $deck, (int) $decklist_id, $name, $description, $tags, $content, $source_deck ?: null);
        $this->entityManager->flush();

        return $this->redirect($this->generateUrl('decks_list'));
    }
}
