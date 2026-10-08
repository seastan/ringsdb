<?php

namespace App\Controller\Review;

use App\Entity\Review;
use App\Model\EditReviewDto;
use App\Repository\ReviewRepository;
use App\Services\Texts;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Attribute\Route;

class EditReviewController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ReviewRepository $reviewRepository,
        private readonly Texts $texts
    ) {
    }

    #[Route(path: '/review/edit', name: 'card_review_edit', methods: ['POST'])]
    public function __invoke(#[MapRequestPayload] EditReviewDto $payload = new EditReviewDto()): Response
    {
        /* @var $user \App\Entity\User */
        $user = $this->getUser();
        if (!$user instanceof \Symfony\Component\Security\Core\User\UserInterface) {
            throw new AccessDeniedHttpException('You are not logged in.');
        }

        $review_id = filter_var($payload->reviewId, FILTER_SANITIZE_NUMBER_INT);
        /* @var $review Review */
        $review = $this->reviewRepository->find($review_id);
        if (!$review instanceof Review) {
            throw new BadRequestHttpException('Unable to find review.');
        }

        if (!$review->getUser()->isEqualTo($user)) {
            throw new AccessDeniedHttpException('You cannot edit this review.');
        }

        $review_raw = trim($payload->review);
        $review_raw = (string) preg_replace('%(?<!\\()\\b(?:(?:https?|ftp)://)(?:((?:(?:[a-z\\d\\x{00a1}-\\x{ffff}]+-?)*[a-z\\d\\x{00a1}-\\x{ffff}]+)(?:\\.(?:[a-z\\d\\x{00a1}-\\x{ffff}]+-?)*[a-z\\d\\x{00a1}-\\x{ffff}]+)*(?:\\.[a-z\\x{00a1}-\\x{ffff}]{2,6}))(?::\\d+)?)(?:[^\\s]*)?%iu', '[$1]($0)', $review_raw);

        $review_html = $this->texts->markdown($review_raw);
        if (!$review_html) {
            return new Response('Your review is empty.');
        }

        $review->setTextMd($review_raw);
        $review->setTextHtml($review_html);

        $this->entityManager->flush();

        return new JsonResponse(['success' => true]);
    }
}
