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
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;

class AjaxSaveController extends AbstractController
{
    use CurrentUserTrait;

    public function __construct(private EntityManagerInterface $entityManager, private DeckRepository $deckRepository, private Decks $decks)
    {
    }

    /**
     * @Route("/deck/save-ajax", name="deck_save_ajax", methods={"POST"})
     */
    public function __invoke(Request $request): JsonResponse
    {
        /* @var $user User */
        $user = $this->currentUser();
        if (count($user->getDecks()) > $user->getMaxNbDecks()) {
            return new JsonResponse(['success' => false, 'error' => 'You have reached the maximum number of decks allowed.'], 422);
        }

        $id = filter_var($request->get('id'), FILTER_SANITIZE_NUMBER_INT);
        $deck = null;
        $source_deck = null;
        if ($id) {
            /* @var $deck \App\Entity\Deck */
            $deck = $this->deckRepository->find($id);
            if (!$deck || !$deck->getUser()->isEqualTo($user)) {
                return new JsonResponse(['success' => false, 'error' => "You don't have access to this deck."], 403);
            }

            $source_deck = $deck;
        } else {
            $deck = new Deck($user);
        }

        $content = json_decode($request->get('content'), true);
        if (!isset($content['main']) || empty($content['main'])) {
            return new JsonResponse(['success' => false, 'error' => 'Cannot save an empty deck.'], 422);
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

        return new JsonResponse(['success' => true, 'id' => $deck->getId()]);
    }
}
