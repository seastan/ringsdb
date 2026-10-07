<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Comment;
use App\Entity\Deck;
use App\Entity\Decklist;
use App\Entity\User;
use App\Repository\CommentRepository;
use App\Repository\DecklistRepository;
use App\Repository\DeckRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class UserAdminController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly CommentRepository $commentRepository,
        private readonly UserRepository $userRepository
    ) {
    }

    /**
     * @Route("/admin/user/find", name="admin_find_user", methods={"GET"})
     */
    public function findAction(): Response
    {
        return $this->render('Admin/find_user.html.twig', ['pagetitle' => 'Admin']);
    }

    /**
     * @Route("/admin/user/find_process", name="admin_find_user_process", methods={"POST"})
     */
    public function processAction(Request $request): RedirectResponse
    {
        $user = null;
        if ($request->request->get('username')) {
            $user = $this->userRepository->findOneBy(['username' => $request->request->get('username')]);
        } else {
            if ($request->request->get('id')) {
                $user = $this->userRepository->find($request->request->get('id'));
            }
        }

        if (!$user) {
            $this->addFlash('warning', 'Cannot find user');

            return $this->redirect($this->generateUrl('admin_find_user'));
        }

        return $this->redirect($this->generateUrl('admin_show_user', ['user_id' => $user->getId()]));
    }

    /**
     * @Route("/admin/user/show/{user_id}", name="admin_show_user", methods={"GET"})
     */
    public function showAction(int $user_id): Response
    {
        /* @var $user User */
        $user = $this->userRepository->find($user_id);
        if (!$user) {
            throw $this->createNotFoundException('User not found');
        }

        return $this->render('Admin/user_admin.html.twig', ['pagetitle' => 'User Admin', 'user' => $user]);
    }

    /**
     * @Route("/admin/user/toggle_locked/{user_id}", name="admin_user_locked_toggle", methods={"GET"})
     */
    public function toggleLockedAction(int $user_id): RedirectResponse
    {
        /* @var $user User */
        $user = $this->userRepository->find($user_id);
        if (!$user) {
            throw $this->createNotFoundException('User not found');
        }

        $user->setLocked(!$user->isLocked());
        $this->entityManager->flush();

        return $this->redirect($this->generateUrl('admin_show_user', ['user_id' => $user->getId()]));
    }

    /**
     * @Route("/admin/user/decklists/{user_id}", name="admin_user_decklists_show", methods={"GET"})
     */
    public function decklistsAction(int $user_id): Response
    {
        /* @var $user User */
        $user = $this->userRepository->find($user_id);
        if (!$user) {
            throw $this->createNotFoundException('User not found');
        }

        return $this->render('Admin/user_decklists.html.twig', ['pagetitle' => 'User Admin', 'user' => $user]);
    }

    /**
     * @Route("/admin/decklist/delete/{decklist_id}", name="admin_decklist_delete", methods={"GET"})
     */
    public function deleteDecklistAction(int $decklist_id, DeckRepository $deckRepository, DecklistRepository $decklistRepository): RedirectResponse
    {
        /* @var $decklist Decklist */
        $decklist = $decklistRepository->find($decklist_id);
        if (!$decklist) {
            throw $this->createNotFoundException('Decklist not found');
        }

        // first we remove the foreign keys in Decklist and Deck pointing to this decklist
        $successors = $decklistRepository->findBy(['precedent' => $decklist]);
        foreach ($successors as $successor) {
            /* @var $successor Decklist */
            $successor->setPrecedent();
        }

        $children = $deckRepository->findBy(['parent' => $decklist]);
        foreach ($children as $child) {
            /* @var $child Deck */
            $child->setParent();
        }

        $this->entityManager->flush();
        // then we remove the decklist itself
        $this->entityManager->remove($decklist);
        $this->entityManager->flush();

        return $this->redirect($this->generateUrl('admin_user_decklists_show', ['user_id' => $decklist->getUser()->getId()]));
    }

    /**
     * @Route("/admin/user/comments/{user_id}", name="admin_user_comments_show", methods={"GET"})
     */
    public function commentsAction(int $user_id): Response
    {
        /* @var $user User */
        $user = $this->userRepository->find($user_id);
        if (!$user) {
            throw $this->createNotFoundException('User not found');
        }

        return $this->render('Admin/user_comments.html.twig', ['pagetitle' => 'User Admin', 'user' => $user]);
    }

    /**
     * @Route(
     *     "/admin/comment/toggle_hidden/{comment_id}",
     *     name="admin_comment_hidden_toggle",
     *     methods={"GET"}
     * )
     */
    public function toggleHiddenCommentAction(int $comment_id): RedirectResponse
    {
        /* @var $comment Comment */
        $comment = $this->commentRepository->find($comment_id);
        if (!$comment) {
            throw $this->createNotFoundException('Comment not found');
        }

        $comment->setIsHidden(!$comment->getIsHidden());
        $this->entityManager->flush();

        return $this->redirect($this->generateUrl('admin_user_comments_show', ['user_id' => $comment->getUser()->getId()]));
    }

    /**
     * @Route("/admin/comment/delete/{comment_id}", name="admin_comment_delete", methods={"GET"})
     */
    public function deleteCommentAction(int $comment_id): RedirectResponse
    {
        /* @var $comment Comment */
        $comment = $this->commentRepository->find($comment_id);
        if (!$comment) {
            throw $this->createNotFoundException('Comment not found');
        }

        $this->entityManager->remove($comment);
        $this->entityManager->flush();

        return $this->redirect($this->generateUrl('admin_user_comments_show', ['user_id' => $comment->getUser()->getId()]));
    }
}
