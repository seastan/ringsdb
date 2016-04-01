<?php

namespace AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class UserAdminController extends Controller {
	public function findAction() {
		return $this->render('AppBundle:Admin:find_user.html.twig', [
			'pagetitle' => "Admin"
		]);
	}

	public function processAction(Request $request) {
		$em = $this->getDoctrine()->getManager();
		$user = null;

		if ($request->request->get('username')) {
			$user = $em->getRepository('AppBundle:User')->findOneBy(['username' => $request->request->get('username')]);
		} else {
			if ($request->request->get('id')) {
				$user = $em->getRepository('AppBundle:User')->find($request->request->get('id'));
			}
		}

		if (!$user) {
			$this->addFlash('warning', "Cannot find user");

			return $this->redirect($this->generateUrl('admin_find_user'));
		}

		return $this->redirect($this->generateUrl('admin_show_user', ['user_id' => $user->getId()]));
	}

	public function showAction($user_id) {
		$em = $this->getDoctrine()->getManager();
		/* @var $user \AppBundle\Entity\User */
		$user = $em->getRepository('AppBundle:User')->find($user_id);
		if (!$user) {
			throw $this->createNotFoundException("User not found");
		}

		return $this->render('AppBundle:Admin:user_admin.html.twig', [
			'pagetitle' => "User Admin",
			'user' => $user,
		]);
	}

	public function toggleLockedAction($user_id) {
		$em = $this->getDoctrine()->getManager();
		/* @var $user \AppBundle\Entity\User */
		$user = $em->getRepository('AppBundle:User')->find($user_id);
		if (!$user) {
			throw $this->createNotFoundException("User not found");
		}

		$user->setLocked(!$user->isLocked());
		$em->flush();

		return $this->redirect($this->generateUrl('admin_show_user', ['user_id' => $user->getId()]));
	}

	public function decklistsAction($user_id) {
		$em = $this->getDoctrine()->getManager();
		/* @var $user \AppBundle\Entity\User */
		$user = $em->getRepository('AppBundle:User')->find($user_id);
		if (!$user) {
			throw $this->createNotFoundException("User not found");
		}

		return $this->render('AppBundle:Admin:user_decklists.html.twig', [
			'pagetitle' => "User Admin",
			'user' => $user,
		]);
	}

	public function deleteDecklistAction($decklist_id) {
		$em = $this->getDoctrine()->getManager();

		/* @var $decklist \AppBundle\Entity\Decklist */
		$decklist = $em->getRepository('AppBundle:Decklist')->find($decklist_id);
		if (!$decklist) {
			throw $this->createNotFoundException("Decklist not found");
		}

		// first we remove the foreign keys in Decklist and Deck pointing to this decklist

		$successors = $em->getRepository('AppBundle:Decklist')->findBy([
			'precedent' => $decklist
		]);
		foreach ($successors as $successor) {
			/* @var $successor \AppBundle\Entity\Decklist */
			$successor->setPrecedent(null);
		}

		$children = $em->getRepository('AppBundle:Deck')->findBy([
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

	public function commentsAction($user_id) {
		$em = $this->getDoctrine()->getManager();
		/* @var $user \AppBundle\Entity\User */
		$user = $em->getRepository('AppBundle:User')->find($user_id);
		if (!$user) {
			throw $this->createNotFoundException("User not found");
		}

		return $this->render('AppBundle:Admin:user_comments.html.twig', [
			'pagetitle' => "User Admin",
			'user' => $user,
		]);
	}

	public function toggleHiddenCommentAction($comment_id) {
		$em = $this->getDoctrine()->getManager();
		/* @var $comment \AppBundle\Entity\Comment */
		$comment = $em->getRepository('AppBundle:Comment')->find($comment_id);
		if (!$comment) {
			throw $this->createNotFoundException("Comment not found");
		}

		$comment->setIsHidden(!$comment->getIsHidden());
		$em->flush();

		return $this->redirect($this->generateUrl('admin_user_comments_show', ['user_id' => $comment->getUser()->getId()]));
	}

	public function deleteCommentAction($comment_id) {
		$em = $this->getDoctrine()->getManager();
		/* @var $comment \AppBundle\Entity\Comment */
		$comment = $em->getRepository('AppBundle:Comment')->find($comment_id);
		if (!$comment) {
			throw $this->createNotFoundException("Comment not found");
		}

		$em->remove($comment);
		$em->flush();

		return $this->redirect($this->generateUrl('admin_user_comments_show', ['user_id' => $comment->getUser()->getId()]));
	}
}
