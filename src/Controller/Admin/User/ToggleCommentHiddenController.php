<?php

declare(strict_types=1);

namespace App\Controller\Admin\User;

use App\Entity\Comment;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Attribute\Route;

class ToggleCommentHiddenController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    #[Route(path: '/admin/comment/toggle_hidden/{comment_id}', name: 'admin_comment_hidden_toggle', methods: ['GET'])]
    public function __invoke(#[MapEntity(id: 'comment_id', message: 'Comment not found')] Comment $comment): RedirectResponse
    {
        $comment->setIsHidden(!$comment->getIsHidden());
        $this->entityManager->flush();

        return $this->redirect($this->generateUrl('admin_user_comments_show', ['user_id' => $comment->getUser()->getId()]));
    }
}
