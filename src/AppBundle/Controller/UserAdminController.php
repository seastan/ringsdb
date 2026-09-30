<?php

namespace AppBundle\Controller;

use AppBundle\Repository\UserRepository;
use AppBundle\Repository\DecklistRepository;
use AppBundle\Repository\DeckRepository;
use AppBundle\Repository\CommentRepository;
use AppBundle\Entity\User;
use AppBundle\Entity\Decklist;
use AppBundle\Entity\Deck;
use AppBundle\Entity\Comment;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;

class UserAdminController extends AbstractController {
	/**
	 * @var CommentRepository
	 */
	private $commentRepository;

	/**
	 * @var UserRepository
	 */
	private $userRepository;

	public function __construct(CommentRepository $commentRepository, UserRepository $userRepository) {
		$this->commentRepository = $commentRepository;
		$this->userRepository = $userRepository;
	}

	/**
	 * @return \Symfony\Component\HttpFoundation\Response
	 */
	public function findAction() {
		return $this->render('AppBundle:Admin:find_user.html.twig', [
			'pagetitle' => "Admin"
		]);
	}

	/**
	 * @return \Symfony\Component\HttpFoundation\RedirectResponse
	 */
	public function processAction(Request $request) {
		$user = null;

		if ($request->request->get('username')) {
			$user = $this->userRepository->findOneBy(['username' => $request->request->get('username')]);
		} else {
			if ($request->request->get('id')) {
				$user = $this->userRepository->find($request->request->get('id'));
			}
		}

		if (!$user) {
			$this->addFlash('warning', "Cannot find user");

			return $this->redirect($this->generateUrl('admin_find_user'));
		}

		return $this->redirect($this->generateUrl('admin_show_user', ['user_id' => $user->getId()]));
	}

	/**
	 * @param mixed $user_id
	 * @return \Symfony\Component\HttpFoundation\Response
	 */
	public function showAction($user_id) {
		/* @var $user \AppBundle\Entity\User */
		$user = $this->userRepository->find($user_id);
		if (!$user) {
			throw $this->createNotFoundException("User not found");
		}

		return $this->render('AppBundle:Admin:user_admin.html.twig', [
			'pagetitle' => "User Admin",
			'user' => $user,
		]);
	}

	/**
	 * @param mixed $user_id
	 * @return \Symfony\Component\HttpFoundation\RedirectResponse
	 */
	public function toggleLockedAction($user_id) {
		$em = $this->getDoctrine()->getManager();
		/* @var $user \AppBundle\Entity\User */
		$user = $this->userRepository->find($user_id);
		if (!$user) {
			throw $this->createNotFoundException("User not found");
		}

		$user->setLocked(!$user->isLocked());
		$em->flush();

		return $this->redirect($this->generateUrl('admin_show_user', ['user_id' => $user->getId()]));
	}

	/**
	 * @param mixed $user_id
	 * @return \Symfony\Component\HttpFoundation\Response
	 */
	public function decklistsAction($user_id) {
		/* @var $user \AppBundle\Entity\User */
		$user = $this->userRepository->find($user_id);
		if (!$user) {
			throw $this->createNotFoundException("User not found");
		}

		return $this->render('AppBundle:Admin:user_decklists.html.twig', [
			'pagetitle' => "User Admin",
			'user' => $user,
		]);
	}

	/**
	 * @param mixed $decklist_id
	 * @return \Symfony\Component\HttpFoundation\RedirectResponse
	 */
	public function deleteDecklistAction($decklist_id, DeckRepository $deckRepository, DecklistRepository $decklistRepository) {
		$em = $this->getDoctrine()->getManager();

		/* @var $decklist \AppBundle\Entity\Decklist */
		$decklist = $decklistRepository->find($decklist_id);
		if (!$decklist) {
			throw $this->createNotFoundException("Decklist not found");
		}

		// first we remove the foreign keys in Decklist and Deck pointing to this decklist

		$successors = $decklistRepository->findBy([
			'precedent' => $decklist
		]);
		foreach ($successors as $successor) {
			/* @var $successor \AppBundle\Entity\Decklist */
			$successor->setPrecedent(null);
		}

		$children = $deckRepository->findBy([
			'parent' => $decklist
		]);
		foreach ($children as $child) {
			/* @var $child \AppBundle\Entity\Deck */
			$child->setParent(null);
		}

		$em->flush();

		// then we remove the decklist itself

		$em->remove($decklist);
		$em->flush();

		return $this->redirect($this->generateUrl('admin_user_decklists_show', ['user_id' => $decklist->getUser()->getId()]));
	}

	/**
	 * @param mixed $user_id
	 * @return \Symfony\Component\HttpFoundation\Response
	 */
	public function commentsAction($user_id) {
		/* @var $user \AppBundle\Entity\User */
		$user = $this->userRepository->find($user_id);
		if (!$user) {
			throw $this->createNotFoundException("User not found");
		}

		return $this->render('AppBundle:Admin:user_comments.html.twig', [
			'pagetitle' => "User Admin",
			'user' => $user,
		]);
	}

	/**
	 * @param mixed $comment_id
	 * @return \Symfony\Component\HttpFoundation\RedirectResponse
	 */
	public function toggleHiddenCommentAction($comment_id) {
		$em = $this->getDoctrine()->getManager();
		/* @var $comment \AppBundle\Entity\Comment */
		$comment = $this->commentRepository->find($comment_id);
		if (!$comment) {
			throw $this->createNotFoundException("Comment not found");
		}

		$comment->setIsHidden(!$comment->getIsHidden());
		$em->flush();

		return $this->redirect($this->generateUrl('admin_user_comments_show', ['user_id' => $comment->getUser()->getId()]));
	}

	/**
	 * @param mixed $comment_id
	 * @return \Symfony\Component\HttpFoundation\RedirectResponse
	 */
	public function deleteCommentAction($comment_id) {
		$em = $this->getDoctrine()->getManager();
		/* @var $comment \AppBundle\Entity\Comment */
		$comment = $this->commentRepository->find($comment_id);
		if (!$comment) {
			throw $this->createNotFoundException("Comment not found");
		}

		$em->remove($comment);
		$em->flush();

		return $this->redirect($this->generateUrl('admin_user_comments_show', ['user_id' => $comment->getUser()->getId()]));
	}
}
