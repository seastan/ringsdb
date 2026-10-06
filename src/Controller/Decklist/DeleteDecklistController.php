<?php

declare(strict_types=1);

namespace App\Controller\Decklist;

use App\Entity\Deck;
use App\Entity\Decklist;
use App\Repository\DecklistRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Annotation\Route;

class DeleteDecklistController extends AbstractController
{
    public function __construct(private readonly EntityManagerInterface $entityManager, private readonly DecklistRepository $decklistRepository)
    {
    }

    /**
     * deletes a decklist if it has no comment, no vote, no favorite.
     *
     * @Route(
     *     "/decklist/delete/{decklist_id}",
     *     name="decklist_delete",
     *     methods={"POST"},
     *     requirements={"decklist_id"="\d+"}
     * )
     */
    public function __invoke(int $decklist_id): RedirectResponse
    {
        $user = $this->getUser();
        if (!$user) {
            throw new AccessDeniedHttpException('You must be logged in for this operation.');
        }

        $decklist = $this->decklistRepository->find($decklist_id);
        if (!$decklist || !$decklist->getUser()->isEqualTo($user)) {
            throw new AccessDeniedHttpException("You don't have access to this decklist.");
        }

        if ($decklist->getNbVotes() || $decklist->getNbfavorites() || $decklist->getNbcomments()) {
            throw new AccessDeniedHttpException('Cannot delete this decklist.');
        }

        $precedent = $decklist->getPrecedent();
        $children_decks = $decklist->getChildren();
        /* @var $children_deck Deck */
        foreach ($children_decks as $children_deck) {
            $children_deck->setParent($precedent);
        }

        $successor_decklists = $decklist->getSuccessors();
        /* @var $successor_decklist Decklist */
        foreach ($successor_decklists as $successor_decklist) {
            $successor_decklist->setPrecedent($precedent);
        }

        $this->entityManager->remove($decklist);
        $this->entityManager->flush();

        return $this->redirect($this->generateUrl('decklists_list', ['type' => 'mine']));
    }
}
