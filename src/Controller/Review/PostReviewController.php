<?php

namespace App\Controller\Review;

use App\Entity\Card;
use App\Entity\Review;
use App\Repository\CardRepository;
use App\Repository\ReviewRepository;
use App\Services\Texts;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class PostReviewController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private ReviewRepository $reviewRepository;
    private Texts $texts;

    public function __construct(
        EntityManagerInterface $entityManager,
        ReviewRepository $reviewRepository,
        Texts $texts
    ) {
        $this->entityManager = $entityManager;
        $this->reviewRepository = $reviewRepository;
        $this->texts = $texts;
    }

    /**
     * @Route("/review/post", name="card_review_post", methods={"POST"})
     */
    public function postAction(Request $request, CardRepository $cardRepository): JsonResponse
    {
        /* @var $user \App\Entity\User */
        $user = $this->getUser();
        if (!$user) {
            throw $this->createAccessDeniedException('You are not logged in.');
        }
        // a user cannot post more reviews than her reputation
        if (count($user->getReviews()) >= $user->getReputation()) {
            throw new \Exception("Your reputation doesn't allow you to write more reviews.");
        }
        $card_id = filter_var($request->get('card_id'), FILTER_SANITIZE_NUMBER_INT);
        /* @var $card Card */
        $card = $cardRepository->find($card_id);
        if (!$card) {
            throw new \Exception('This card does not exist.');
        }
        if (!$card->getPack()->getDateRelease()) {
            throw new \Exception('You may not write a review for an unreleased card.');
        }
        // checking the user didn't already write a review for that card
        $review = $this->reviewRepository->findOneBy(['card' => $card, 'user' => $user]);
        if ($review) {
            throw new \Exception('You cannot write more than 1 review for a given card.');
        }
        $review_raw = trim($request->get('review'));
        $review_raw = (string) preg_replace('%(?<!\\()\\b(?:(?:https?|ftp)://)(?:((?:(?:[a-z\\d\\x{00a1}-\\x{ffff}]+-?)*[a-z\\d\\x{00a1}-\\x{ffff}]+)(?:\\.(?:[a-z\\d\\x{00a1}-\\x{ffff}]+-?)*[a-z\\d\\x{00a1}-\\x{ffff}]+)*(?:\\.[a-z\\x{00a1}-\\x{ffff}]{2,6}))(?::\\d+)?)(?:[^\\s]*)?%iu', '[$1]($0)', $review_raw);
        $review_html = $this->texts->markdown($review_raw);
        if (!$review_html) {
            throw new \Exception('Your review is empty.');
        }
        $review = new Review();
        $review->setCard($card);
        $review->setUser($user);
        $review->setTextMd($review_raw);
        $review->setTextHtml($review_html);
        $review->setNbVotes(0);
        $this->entityManager->persist($review);
        $this->entityManager->flush();

        return new JsonResponse(['success' => true]);
    }
}
