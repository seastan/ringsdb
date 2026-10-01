<?php

namespace App\Controller;

use App\Entity\Comment;
use App\Entity\Deck;
use App\Entity\Decklist;
use App\Entity\User;
use App\Repository\CommentRepository;
use App\Repository\DecklistRepository;
use App\Repository\DeckRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class UserAdminController extends AbstractController
{
    /**
     * @var CommentRepository
     */
    private $commentRepository;
    /**
     * @var UserRepository
     */
    private $userRepository;

    public function __construct(CommentRepository $commentRepository, UserRepository $userRepository)
    {
        $this->commentRepository = $commentRepository;
        $this->userRepository = $userRepository;
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @Route("/admin/user/find", name="admin_find_user", methods={"GET"})
     */
    public function findAction()
    {
        return $this->render('Admin/find_user.html.twig', ['pagetitle' => 'Admin']);
    }

    /**
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     *
     * @Route("/admin/user/find_process", name="admin_find_user_process", methods={"POST"})
     */
    public function processAction(Request $request)
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
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @Route("/admin/user/show/{user_id}", name="admin_show_user", methods={"GET"})
     */
    public function showAction($user_id)
    {
        /* @var $user \App\Entity\User */
        $user = $this->userRepository->find($user_id);
        if (!$user) {
            throw $this->createNotFoundException('User not found');
        }

        return $this->render('Admin/user_admin.html.twig', ['pagetitle' => 'User Admin', 'user' => $user]);
    }

    /**
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     *
     * @Route("/admin/user/toggle_locked/{user_id}", name="admin_user_locked_toggle", methods={"GET"})
     */
    public function toggleLockedAction($user_id)
    {
        $em = $this->getDoctrine()->getManager();
        /* @var $user \App\Entity\User */
        $user = $this->userRepository->find($user_id);
        if (!$user) {
            throw $this->createNotFoundException('User not found');
        }
        $user->setLocked(!$user->isLocked());
        $em->flush();

        return $this->redirect($this->generateUrl('admin_show_user', ['user_id' => $user->getId()]));
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @Route("/admin/user/decklists/{user_id}", name="admin_user_decklists_show", methods={"GET"})
     */
    public function decklistsAction($user_id)
    {
        /* @var $user \App\Entity\User */
        $user = $this->userRepository->find($user_id);
        if (!$user) {
            throw $this->createNotFoundException('User not found');
        }

        return $this->render('Admin/user_decklists.html.twig', ['pagetitle' => 'User Admin', 'user' => $user]);
    }

    /**
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     *
     * @Route("/admin/decklist/delete/{decklist_id}", name="admin_decklist_delete", methods={"GET"})
     */
    public function deleteDecklistAction($decklist_id, DeckRepository $deckRepository, DecklistRepository $decklistRepository)
    {
        $em = $this->getDoctrine()->getManager();
        /* @var $decklist \App\Entity\Decklist */
        $decklist = $decklistRepository->find($decklist_id);
        if (!$decklist) {
            throw $this->createNotFoundException('Decklist not found');
        }
        // first we remove the foreign keys in Decklist and Deck pointing to this decklist
        $successors = $decklistRepository->findBy(['precedent' => $decklist]);
        foreach ($successors as $successor) {
            /* @var $successor \App\Entity\Decklist */
            $successor->setPrecedent(null);
        }
        $children = $deckRepository->findBy(['parent' => $decklist]);
        foreach ($children as $child) {
            /* @var $child \App\Entity\Deck */
            $child->setParent(null);
        }
        $em->flush();
        // then we remove the decklist itself
        $em->remove($decklist);
        $em->flush();

        return $this->redirect($this->generateUrl('admin_user_decklists_show', ['user_id' => $decklist->getUser()->getId()]));
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @Route("/admin/user/comments/{user_id}", name="admin_user_comments_show", methods={"GET"})
     */
    public function commentsAction($user_id)
    {
        /* @var $user \App\Entity\User */
        $user = $this->userRepository->find($user_id);
        if (!$user) {
            throw $this->createNotFoundException('User not found');
        }

        return $this->render('Admin/user_comments.html.twig', ['pagetitle' => 'User Admin', 'user' => $user]);
    }

    /**
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     *
     * @Route(
     *     "/admin/comment/toggle_hidden/{comment_id}",
     *     name="admin_comment_hidden_toggle",
     *     methods={"GET"}
     * )
     */
    public function toggleHiddenCommentAction($comment_id)
    {
        $em = $this->getDoctrine()->getManager();
        /* @var $comment \App\Entity\Comment */
        $comment = $this->commentRepository->find($comment_id);
        if (!$comment) {
            throw $this->createNotFoundException('Comment not found');
        }
        $comment->setIsHidden(!$comment->getIsHidden());
        $em->flush();

        return $this->redirect($this->generateUrl('admin_user_comments_show', ['user_id' => $comment->getUser()->getId()]));
    }

    /**
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     *
     * @Route("/admin/comment/delete/{comment_id}", name="admin_comment_delete", methods={"GET"})
     */
    public function deleteCommentAction($comment_id)
    {
        $em = $this->getDoctrine()->getManager();
        /* @var $comment \App\Entity\Comment */
        $comment = $this->commentRepository->find($comment_id);
        if (!$comment) {
            throw $this->createNotFoundException('Comment not found');
        }
        $em->remove($comment);
        $em->flush();

        return $this->redirect($this->generateUrl('admin_user_comments_show', ['user_id' => $comment->getUser()->getId()]));
    }
}
