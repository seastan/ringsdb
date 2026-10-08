<?php

declare(strict_types=1);

namespace App\Controller\Admin\User;

use App\Entity\Comment;
use App\Repository\CommentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Attribute\Route;

class DeleteCommentController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly CommentRepository $commentRepository
    ) {
    }

    #[Route(path: '/admin/comment/delete/{comment_id}', name: 'admin_comment_delete', methods: ['GET'])]
    public function __invoke(int $comment_id): RedirectResponse
    {
        /* @var $comment Comment */
        $comment = $this->commentRepository->find($comment_id);
        if (!$comment instanceof Comment) {
            throw $this->createNotFoundException('Comment not found');
        }

        $this->entityManager->remove($comment);
        $this->entityManager->flush();

        return $this->redirect($this->generateUrl('admin_user_comments_show', ['user_id' => $comment->getUser()->getId()]));
    }
}
