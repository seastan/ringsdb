<?php

declare(strict_types=1);

namespace App\Controller\Questlog;

use App\Entity\User;
use App\Repository\QuestlogCommentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;

class HideCommentQuestlogController extends AbstractController
{
    private QuestlogCommentRepository $questlogCommentRepository;
    private EntityManagerInterface $entityManager;

    public function __construct(
        EntityManagerInterface $entityManager,
        QuestlogCommentRepository $questlogCommentRepository
    ) {
        $this->questlogCommentRepository = $questlogCommentRepository;
        $this->entityManager = $entityManager;
    }

    /**
     * hides a comment, or if $hidden is false, unhide a comment.
     *
     * @Route(
     *     "/user/questlog_hidecomment/{comment_id}/{hidden}",
     *     name="questlog_comment_hide",
     *     methods={"POST"}
     * )
     */
    public function __invoke(int $comment_id, int $hidden): Response
    {
        /* @var $user User */
        $user = $this->getUser();
        if (!$user) {
            throw $this->createAccessDeniedException('You are not logged in.');
        }
        $comment = $this->questlogCommentRepository->find($comment_id);
        if (!$comment) {
            throw new BadRequestHttpException('Unable to find comment');
        }
        if ($comment->getQuestlog()->getUser()->getId() !== $user->getId()) {
            return new Response(json_encode("You don't have permission to edit this comment."));
        }
        $comment->setIsHidden((bool) $hidden);
        $this->entityManager->flush();

        return new Response(json_encode(true));
    }
}
