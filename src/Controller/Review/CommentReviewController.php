<?php

namespace App\Controller\Review;

use App\Entity\Review;
use App\Entity\Reviewcomment;
use App\Repository\ReviewRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class CommentReviewController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ReviewRepository $reviewRepository
    ) {
    }

    #[Route(path: '/review/comment', name: 'card_reviewcomment_post', methods: ['POST'])]
    public function __invoke(Request $request): JsonResponse
    {
        /* @var $user \App\Entity\User */
        $user = $this->getUser();
        if (!$user instanceof \Symfony\Component\Security\Core\User\UserInterface) {
            throw $this->createAccessDeniedException('You are not logged in.');
        }

        $review_id = filter_var($request->request->get('comment_review_id'), FILTER_SANITIZE_NUMBER_INT);
        /* @var $review Review */
        $review = $this->reviewRepository->find($review_id);
        if (!$review instanceof Review) {
            throw new \Exception('Unable to find review.');
        }

        $comment_text = trim($request->request->getString('comment'));
        $comment_text = htmlspecialchars($comment_text);
        if (!$comment_text) {
            throw new \Exception('Your comment is empty.');
        }

        $comment = new Reviewcomment($user, $review, $comment_text);

        $now = new \DateTime();
        $review->setDateLastComment($now);
        $this->entityManager->persist($comment);
        $this->entityManager->flush();

        return new JsonResponse(['success' => true]);
    }
}
