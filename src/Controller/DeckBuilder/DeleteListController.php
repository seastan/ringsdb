<?php

declare(strict_types=1);

namespace App\Controller\DeckBuilder;

use App\Controller\CurrentUserTrait;
use App\Repository\DeckRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class DeleteListController extends AbstractController
{
    use CurrentUserTrait;

    public function __construct(
        private EntityManagerInterface $entityManager,
        private DeckRepository $deckRepository
    ) {
    }

    #[Route(path: '/deck/delete_list', name: 'deck_delete_list', methods: ['POST'])]
    public function __invoke(Request $request): RedirectResponse
    {
        $list_id = explode('-', $request->request->getString('ids'));
        foreach ($list_id as $id) {
            /* @var $deck \App\Entity\Deck */
            $deck = $this->deckRepository->find($id);
            if (!$deck) {
                continue;
            }

            if ($this->currentUser()->getId() != $deck->getUser()->getId()) {
                continue;
            }

            foreach ($deck->getChildren() as $decklist) {
                $decklist->setParent();
            }

            $this->entityManager->remove($deck);
        }

        $this->entityManager->flush();
        $this->addFlash('notice', 'Decks deleted.');

        return $this->redirect($this->generateUrl('decks_list'));
    }
}
