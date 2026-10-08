<?php

declare(strict_types=1);

namespace App\Controller\Admin\Card;

use App\Controller\Admin\DeleteFormTrait;
use App\Entity\Card;
use App\Repository\CardRepository;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class ForceDeleteCardController extends AbstractController
{
    use DeleteFormTrait;

    public function __construct(
        private readonly CardRepository $cardRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly Connection $connection
    ) {
    }

    /**
     * Forcibly deletes a Card entity and all its deck/decklist slot references.
     */
    #[Route(path: '/admin/card/{id}/force_delete', name: 'admin_card_force_delete', methods: ['POST', 'DELETE'])]
    public function __invoke(Request $request, int $id): RedirectResponse
    {
        $form = $this->createDeleteForm($id);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $entity = $this->cardRepository->find($id);
            if (!$entity instanceof Card) {
                throw $this->createNotFoundException('Unable to find Card entity.');
            }

            $query = 'DELETE FROM deckslot WHERE card_id = '.$id;
            $this->connection->executeQuery($query, []);
            $query = 'DELETE FROM decksideslot WHERE card_id = '.$id;
            $this->connection->executeQuery($query, []);
            $query = 'DELETE FROM decklistslot WHERE card_id = '.$id;
            $this->connection->executeQuery($query, []);
            $query = 'DELETE FROM decklistsideslot WHERE card_id = '.$id;
            $this->connection->executeQuery($query, []);
            $query = 'DELETE FROM card_printing WHERE card_id = '.$id;
            $this->connection->executeQuery($query, []);
            $query = 'DELETE FROM reviewvote WHERE review_id IN (SELECT id FROM review WHERE card_id = '.$id.')';
            $this->connection->executeQuery($query, []);
            $query = 'DELETE FROM review WHERE card_id = '.$id;
            $this->connection->executeQuery($query, []);
            $this->entityManager->remove($entity);
            $this->entityManager->flush();
        }

        return $this->redirect($this->generateUrl('admin_card'));
    }
}
