<?php

declare(strict_types=1);

namespace App\Controller\Admin\Card;

use App\Controller\Admin\DeleteFormTrait;
use App\Entity\Card;
use App\Entity\Decklistsideslot;
use App\Entity\Decklistslot;
use App\Entity\Decksideslot;
use App\Entity\Deckslot;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class ForceDeleteCardController extends AbstractController
{
    use DeleteFormTrait;

    public function __construct(
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    /**
     * Forcibly deletes a Card entity and all its deck/decklist slot references.
     */
    #[Route(path: '/admin/card/{id}/force_delete', name: 'admin_card_force_delete', methods: ['POST', 'DELETE'])]
    public function __invoke(Request $request, #[MapEntity(message: 'Unable to find Card entity.')] Card $entity): RedirectResponse
    {
        $form = $this->createDeleteForm($entity->getId());
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->wrapInTransaction(function (EntityManagerInterface $entityManager) use ($entity): void {
                // bulk deletes: a card can be in many decks
                foreach ([Deckslot::class, Decksideslot::class, Decklistslot::class, Decklistsideslot::class] as $slotClass) {
                    $entityManager->createQuery('DELETE FROM '.$slotClass.' s WHERE s.card = :card')
                        ->setParameter('card', $entity)
                        ->execute();
                }

                // the votes go with the reviews (join table), the printings with the card (cascade)
                foreach ($entity->getReviews() as $review) {
                    foreach ($review->getComments() as $comment) {
                        $entityManager->remove($comment);
                    }
                    $entityManager->remove($review);
                }
                $entityManager->remove($entity);
            });
        }

        return $this->redirect($this->generateUrl('admin_card'));
    }
}
