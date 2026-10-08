<?php

declare(strict_types=1);

namespace App\Controller\Decklist;

use App\Entity\Comment;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Attribute\Route;

class HideCommentDecklistController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    /**
     * hides a comment, or if $hidden is false, unhide a comment.
     */
    #[Route(path: '/user/hidecomment/{comment_id}/{hidden}', name: 'decklist_comment_hide', methods: ['POST'])]
    public function __invoke(#[MapEntity(id: 'comment_id', message: 'Unable to find comment')] Comment $comment, int $hidden): Response
    {
        /* @var $user User */
        $user = $this->getUser();
        if (!$user instanceof \Symfony\Component\Security\Core\User\UserInterface) {
            throw new AccessDeniedHttpException('You must be logged in to comment.');
        }

        if (!$comment->getDecklist()->getUser()->isEqualTo($user)) {
            return new JsonResponse("You don't have permission to edit this comment.");
        }

        $comment->setIsHidden((bool) $hidden);
        $this->entityManager->flush();

        return new JsonResponse(true);
    }
}
