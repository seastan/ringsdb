<?php

declare(strict_types=1);

namespace App\Controller\Decklist;

use App\Entity\User;
use App\Repository\CommentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;

class HideCommentDecklistController extends AbstractController
{
    private EntityManagerInterface $entityManager;

    public function __construct(
        EntityManagerInterface $entityManager
    ) {
        $this->entityManager = $entityManager;
    }

    /**
     * hides a comment, or if $hidden is false, unhide a comment.
     *
     * @Route("/user/hidecomment/{comment_id}/{hidden}", name="decklist_comment_hide", methods={"POST"})
     */
    public function __invoke($comment_id, $hidden, CommentRepository $commentRepository): Response
    {
        /* @var $user User */
        $user = $this->getUser();
        if (!$user) {
            throw new AccessDeniedHttpException('You must be logged in to comment.');
        }
        $comment = $commentRepository->find($comment_id);
        if (!$comment) {
            throw new BadRequestHttpException('Unable to find comment');
        }
        if ($comment->getDecklist()->getUser()->getId() !== $user->getId()) {
            return new Response(json_encode("You don't have permission to edit this comment."));
        }
        $comment->setIsHidden((bool) $hidden);
        $this->entityManager->flush();

        return new Response(json_encode(true));
    }
}
