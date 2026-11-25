<?php

namespace App\Controller\Review;

use App\Entity\Review;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

class RemoveReviewController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    #[Route(path: '/review/remove/{id}', name: 'card_review_remove')]
    public function __invoke(#[MapEntity(message: 'Unable to find review.')] Review $review): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof \Symfony\Component\Security\Core\User\UserInterface || !in_array('ROLE_SUPER_ADMIN', $user->getRoles())) {
            throw $this->createAccessDeniedException('No user or not admin');
        }

        $votes = $review->getVotes();
        foreach ($votes as $vote) {
            $review->removeVote($vote);
        }

        $this->entityManager->remove($review);
        $this->entityManager->flush();

        return new JsonResponse(['success' => true]);
    }
}
