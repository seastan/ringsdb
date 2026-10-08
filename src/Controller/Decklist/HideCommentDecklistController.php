<?php

declare(strict_types=1);

namespace App\Controller\Decklist;

use App\Entity\User;
use App\Repository\CommentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Attribute\Route;

class HideCommentDecklistController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly CommentRepository $commentRepository
    ) {
    }

    /**
     * hides a comment, or if $hidden is false, unhide a comment.
     */
    #[Route(path: '/user/hidecomment/{comment_id}/{hidden}', name: 'decklist_comment_hide', methods: ['POST'])]
    public function __invoke(int $comment_id, int $hidden): Response
    {
        /* @var $user User */
        $user = $this->getUser();
        if (!$user instanceof \Symfony\Component\Security\Core\User\UserInterface) {
            throw new AccessDeniedHttpException('You must be logged in to comment.');
        }

        $comment = $this->commentRepository->find($comment_id);
        if (!$comment instanceof \App\Entity\Comment) {
            throw new BadRequestHttpException('Unable to find comment');
        }

        if (!$comment->getDecklist()->getUser()->isEqualTo($user)) {
            return new JsonResponse("You don't have permission to edit this comment.");
        }

        $comment->setIsHidden((bool) $hidden);
        $this->entityManager->flush();

        return new JsonResponse(true);
    }
}
