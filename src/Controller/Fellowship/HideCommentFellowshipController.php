<?php

declare(strict_types=1);

namespace App\Controller\Fellowship;

use App\Entity\User;
use App\Repository\FellowshipCommentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;

class HideCommentFellowshipController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private FellowshipCommentRepository $fellowshipCommentRepository;

    public function __construct(
        EntityManagerInterface $entityManager,
        FellowshipCommentRepository $fellowshipCommentRepository
    ) {
        $this->entityManager = $entityManager;
        $this->fellowshipCommentRepository = $fellowshipCommentRepository;
    }

    /**
     * hides a comment, or if $hidden is false, unhide a comment.
     *
     * @Route(
     *     "/user/fellowship_hidecomment/{comment_id}/{hidden}",
     *     name="fellowship_comment_hide",
     *     methods={"POST"}
     * )
     */
    public function __invoke(int $comment_id, int $hidden): Response
    {
        /* @var $user User */
        $user = $this->getUser();
        if (!$user) {
            throw new AccessDeniedHttpException('You must be logged in to comment.');
        }
        $comment = $this->fellowshipCommentRepository->find($comment_id);
        if (!$comment) {
            throw new BadRequestHttpException('Unable to find comment');
        }
        if (!$comment->getFellowship()->getUser()->isEqualTo($user)) {
            return new JsonResponse("You don't have permission to edit this comment.");
        }
        $comment->setIsHidden((bool) $hidden);
        $this->entityManager->flush();

        return new JsonResponse(true);
    }
}
