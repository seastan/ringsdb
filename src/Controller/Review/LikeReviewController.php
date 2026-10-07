<?php

namespace App\Controller\Review;

use App\Entity\Review;
use App\Repository\ReviewRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class LikeReviewController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ReviewRepository $reviewRepository
    ) {
    }

    /**
     * @Route("/review/like", name="card_review_like", methods={"POST"})
     */
    public function likeAction(Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            throw $this->createAccessDeniedException('You are not logged in.');
        }

        $review_id = filter_var($request->request->get('id'), FILTER_SANITIZE_NUMBER_INT);
        /* @var $review Review */
        $review = $this->reviewRepository->find($review_id);
        if (!$review) {
            throw new \Exception('Unable to find review.');
        }

        // a user cannot vote on her own review
        if (!$review->getUser()->isEqualTo($user)) {
            // checking if the user didn't already vote on that review
            $query = $this->reviewRepository->createQueryBuilder('r')->innerJoin('r.votes', 'u')->where('r.id = :review_id')->andWhere('u.id = :user_id')->setParameter('review_id', $review_id)->setParameter('user_id', $user->getId())->getQuery();
            $result = $query->getResult();
            if (empty($result)) {
                /* @var $author \App\Entity\User */
                $author = $review->getUser();
                $author->setReputation($author->getReputation() + 1);
                $review->addVote($user);
                $review->setDateUpdate(new \DateTime());
                $review->setNbVotes($review->getNbVotes() + 1);
                $this->entityManager->flush();
            }
        }

        return new JsonResponse(['success' => true, 'nbVotes' => $review->getNbVotes()]);
    }
}
