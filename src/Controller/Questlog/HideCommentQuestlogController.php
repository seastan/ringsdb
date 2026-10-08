<?php

declare(strict_types=1);

namespace App\Controller\Questlog;

use App\Entity\QuestlogComment;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HideCommentQuestlogController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    /**
     * hides a comment, or if $hidden is false, unhide a comment.
     */
    #[Route(path: '/user/questlog_hidecomment/{comment_id}/{hidden}', name: 'questlog_comment_hide', methods: ['POST'])]
    public function __invoke(#[MapEntity(id: 'comment_id', message: 'Unable to find comment')] QuestlogComment $comment, int $hidden): Response
    {
        /* @var $user User */
        $user = $this->getUser();
        if (!$user instanceof \Symfony\Component\Security\Core\User\UserInterface) {
            throw $this->createAccessDeniedException('You are not logged in.');
        }

        if (!$comment->getQuestlog()->getUser()->isEqualTo($user)) {
            return new JsonResponse("You don't have permission to edit this comment.");
        }

        $comment->setIsHidden((bool) $hidden);
        $this->entityManager->flush();

        return new JsonResponse(true);
    }
}
