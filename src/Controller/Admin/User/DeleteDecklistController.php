<?php

declare(strict_types=1);

namespace App\Controller\Admin\User;

use App\Entity\Deck;
use App\Entity\Decklist;
use App\Repository\DecklistRepository;
use App\Repository\DeckRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Attribute\Route;

class DeleteDecklistController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    #[Route(path: '/admin/decklist/delete/{decklist_id}', name: 'admin_decklist_delete', methods: ['GET'])]
    public function __invoke(int $decklist_id, DeckRepository $deckRepository, DecklistRepository $decklistRepository): RedirectResponse
    {
        /* @var $decklist Decklist */
        $decklist = $decklistRepository->find($decklist_id);
        if (!$decklist instanceof Decklist) {
            throw $this->createNotFoundException('Decklist not found');
        }

        // first we remove the foreign keys in Decklist and Deck pointing to this decklist
        $successors = $decklistRepository->findBy(['precedent' => $decklist]);
        foreach ($successors as $successor) {
            /* @var $successor Decklist */
            $successor->setPrecedent();
        }

        $children = $deckRepository->findBy(['parent' => $decklist]);
        foreach ($children as $child) {
            /* @var $child Deck */
            $child->setParent();
        }

        $this->entityManager->flush();
        // then we remove the decklist itself
        $this->entityManager->remove($decklist);
        $this->entityManager->flush();

        return $this->redirect($this->generateUrl('admin_user_decklists_show', ['user_id' => $decklist->getUser()->getId()]));
    }
}
