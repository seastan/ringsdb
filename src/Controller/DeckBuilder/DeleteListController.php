<?php

declare(strict_types=1);

namespace App\Controller\DeckBuilder;

use App\Controller\CurrentUserTrait;
use App\Repository\DeckRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class DeleteListController extends AbstractController
{
    use CurrentUserTrait;

    private EntityManagerInterface $entityManager;
    private DeckRepository $deckRepository;

    public function __construct(
        EntityManagerInterface $entityManager,
        DeckRepository $deckRepository
    ) {
        $this->entityManager = $entityManager;
        $this->deckRepository = $deckRepository;
    }

    /**
     * @Route("/deck/delete_list", name="deck_delete_list", methods={"POST"})
     */
    public function __invoke(Request $request): RedirectResponse
    {
        $list_id = explode('-', $request->get('ids'));
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
                $decklist->setParent(null);
            }
            $this->entityManager->remove($deck);
        }
        $this->entityManager->flush();
        $this->get('session')->getFlashBag()->set('notice', 'Decks deleted.');

        return $this->redirect($this->generateUrl('decks_list'));
    }
}
