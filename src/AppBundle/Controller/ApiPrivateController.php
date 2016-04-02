<?php

namespace AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

class ApiPrivateController extends Controller {
	public function listDecksAction(Request $request) {
		$response = new Response();

        /* @var $em \Doctrine\ORM\EntityManager */
        $em = $this->getDoctrine()->getManager();

        /* @var $decklists \AppBundle\Entity\Decklist[] */
        $decklists = $em->getRepository('AppBundle:Decklist')->findBy(['user' => $this->getUser()], ['dateCreation' => 'DESC']);

        foreach($decklists as &$decklist) {
            $decklist->setDescriptionMd('');
        }

        /* @var $decks \AppBundle\Entity\Deck[] */
        $decks = $em->getRepository('AppBundle:Deck')->findBy(['user' => $this->getUser()], ['dateCreation' => 'DESC']);

        foreach($decks as &$deck) {
            $deck->setDescriptionMd('');
        }

        $decklists = array_merge($decklists, $decks);

        $dateUpdates = array_map(function($deck) {
            /* @var $deck \AppBundle\Entity\Deck */
			return $deck->getDateUpdate();
		}, $decklists);

        if (count($dateUpdates)) {
            $response->setLastModified(max($dateUpdates));
            if ($response->isNotModified($request)) {
                return $response;
            }
        }

		$content = json_encode($decklists);

		$response->headers->set('Content-Type', 'application/json');
		$response->setContent($content);

		return $response;
	}

	public function listUserDecksAction($username, Request $request) {
		$response = new Response();

        /* @var $em \Doctrine\ORM\EntityManager */
        $em = $this->getDoctrine()->getManager();

        /* @var $user \AppBundle\Entity\User */
        $user = $em->getRepository('AppBundle:User')->findOneBy(['username' => $username]);

        if (!$user) {
            $content = json_encode([
                'success' => false,
                'error' => 'This user does not exists.'
            ]);

            $response->headers->set('Content-Type', 'application/json');
            $response->setContent($content);

            return $response;
        }

        $show_private_decks = $user->getIsShareDecks() || $user->getId() == $this->getUser()->getId();

        /* @var $decklists \AppBundle\Entity\Decklist[] */
        $decklists = $em->getRepository('AppBundle:Decklist')->findBy(['user' => $user], ['dateCreation' => 'DESC']);

        foreach($decklists as &$decklist) {
            $decklist->setDescriptionMd('');
        }

        if ($show_private_decks) {
            /* @var $decks \AppBundle\Entity\Deck[] */
            $decks = $em->getRepository('AppBundle:Deck')->findBy(['user' => $user], ['dateCreation' => 'DESC']);

            foreach($decks as &$deck) {
                $deck->setDescriptionMd('');
            }

            $decklists = array_merge($decklists, $decks);
        }

        $dateUpdates = array_map(function($deck) {
            /* @var $deck \AppBundle\Entity\Deck */
            return $deck->getDateUpdate();
		}, $decklists);

        if (count($dateUpdates)) {
            $response->setLastModified(max($dateUpdates));
            if ($response->isNotModified($request)) {
                return $response;
            }
        }

		$content = json_encode($decklists);

		$response->headers->set('Content-Type', 'application/json');
		$response->setContent($content);

		return $response;
	}

	/*
	 * Get the description of one Deck of the authenticated user
	 */
	public function loadDeckAction($id, Request $request) {
		$response = new Response();

        /* @var $em \Doctrine\ORM\EntityManager */
        $em = $this->getDoctrine()->getManager();

        /* @var $deck \AppBundle\Entity\Deck */
		$deck = $em->getRepository('AppBundle:Deck')->find($id);

        if (!$deck) {
            $content = json_encode([
                'success' => false,
                'error' => 'This deck does not exists.'
            ]);

            $response->headers->set('Content-Type', 'application/json');
            $response->setContent($content);

            return $response;
        }

        /* @var $user \AppBundle\Entity\User */
        $user = $deck->getUser();
        if (!$user->getIsShareDecks() && $user != $this->getUser()) {
            $content = json_encode([
                'success' => false,
                'error' => 'You are not allowed to view this deck. To get access, you can ask the deck owner to enable "Share my decks" on their account.'
            ]);

            $response->headers->set('Content-Type', 'application/json');
            $response->setContent($content);

            return $response;
        }

		$response->setLastModified($deck->getDateUpdate());
		if ($response->isNotModified($request)) {
			return $response;
		}

		$content = json_encode($deck);

		$response->headers->set('Content-Type', 'application/json');
		$response->setContent($content);

		return $response;
	}
}
