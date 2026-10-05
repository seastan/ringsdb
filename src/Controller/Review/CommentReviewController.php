<?php

namespace App\Controller\Review;

use App\Entity\Review;
use App\Entity\Reviewcomment;
use App\Repository\ReviewRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class CommentReviewController extends AbstractController
{
    private EntityManagerInterface $entityManager;

    private ReviewRepository $reviewRepository;

    public function __construct(
        EntityManagerInterface $entityManager,
        ReviewRepository $reviewRepository
    ) {
        $this->entityManager = $entityManager;
        $this->reviewRepository = $reviewRepository;
    }

    /**
     * @Route("/review/comment", name="card_reviewcomment_post", methods={"POST"})
     */
    public function commentAction(Request $request): JsonResponse
    {
        /* @var $user \App\Entity\User */
        $user = $this->getUser();
        if (!$user) {
            throw $this->createAccessDeniedException('You are not logged in.');
        }

        $review_id = filter_var($request->get('comment_review_id'), FILTER_SANITIZE_NUMBER_INT);
        /* @var $review Review */
        $review = $this->reviewRepository->find($review_id);
        if (!$review) {
            throw new \Exception('Unable to find review.');
        }

        $comment_text = trim($request->get('comment'));
        $comment_text = htmlspecialchars($comment_text);
        if (!$comment_text) {
            throw new \Exception('Your comment is empty.');
        }

        $comment = new Reviewcomment();
        $comment->setReview($review);
        $comment->setUser($user);
        $comment->setText($comment_text);

        $now = new \DateTime();
        $review->setDateLastComment($now);
        $this->entityManager->persist($comment);
        $this->entityManager->flush();

        return new JsonResponse(['success' => true]);
    }
}
