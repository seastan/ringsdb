<?php

declare(strict_types=1);

namespace App\Controller\DeckBuilder;

use App\Controller\CurrentUserTrait;
use App\Repository\DeckRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Annotation\Route;

class DeleteDeckController extends AbstractController
{
    use CurrentUserTrait;

    public function __construct(
        private EntityManagerInterface $entityManager,
        private DeckRepository $deckRepository
    ) {
    }

    /**
     * @Route("/deck/delete", name="deck_delete", methods={"POST"})
     */
    public function __invoke(Request $request): RedirectResponse
    {
        $deck_id = filter_var($request->get('deck_id'), FILTER_SANITIZE_NUMBER_INT);
        /* @var $deck \App\Entity\Deck */
        $deck = $this->deckRepository->find($deck_id);
        if (!$deck) {
            return $this->redirect($this->generateUrl('decks_list'));
        }

        if ($this->currentUser()->getId() != $deck->getUser()->getId()) {
            throw new AccessDeniedHttpException("You don't have access to this deck.");
        }

        if (count($deck->getFellowships())) {
            $this->addFlash('error', "You can't delete a deck that is member of a fellowship.");
        } else {
            foreach ($deck->getChildren() as $decklist) {
                $decklist->setParent();
            }

            $this->entityManager->remove($deck);
            $this->entityManager->flush();
            $this->addFlash('notice', 'Deck deleted.');
        }

        return $this->redirect($this->generateUrl('decks_list'));
    }
}
