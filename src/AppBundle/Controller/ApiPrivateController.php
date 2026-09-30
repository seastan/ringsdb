<?php

namespace AppBundle\Controller;

use AppBundle\Repository\UserRepository;
use AppBundle\Repository\DecklistRepository;
use AppBundle\Repository\DeckRepository;
use AppBundle\Entity\Deck;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

class ApiPrivateController extends AbstractController {
    use CurrentUserTrait;

	/**
	 * @var DeckRepository
	 */
	private $deckRepository;

	/**
	 * @var DecklistRepository
	 */
	private $decklistRepository;

	public function __construct(DeckRepository $deckRepository, DecklistRepository $decklistRepository) {
		$this->deckRepository = $deckRepository;
		$this->decklistRepository = $decklistRepository;
	}

	/**
	 * @return \Symfony\Component\HttpFoundation\Response
	 */
	public function listDecksAction(Request $request) {
		$response = new Response();

        /* @var $em \Doctrine\ORM\EntityManager */
        $em = $this->getDoctrine()->getManager();

        /* @var $decklists \AppBundle\Entity\Decklist[] */
        $decklists = $this->decklistRepository->findBy(['user' => $this->getUser()], ['dateCreation' => 'DESC', 'id' => 'DESC']);

        foreach($decklists as &$decklist) {
            $decklist->setDescriptionMd('');
        }

        /* @var $decks \AppBundle\Entity\Deck[] */
        $decks = $this->deckRepository->findBy(['user' => $this->getUser()], ['dateCreation' => 'DESC', 'id' => 'DESC']);

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

	/**
	 * @param mixed $username
	 * @return \Symfony\Component\HttpFoundation\Response
	 */
	public function listUserDecksAction($username, Request $request, UserRepository $userRepository) {
		$response = new Response();

        /* @var $em \Doctrine\ORM\EntityManager */
        $em = $this->getDoctrine()->getManager();

        /* @var $user \AppBundle\Entity\User */
        $user = $userRepository->findOneBy(['username' => $username]);

        if (!$user) {
            $content = json_encode([
                'success' => false,
                'error' => 'This user does not exist.'
            ]);

            $response->headers->set('Content-Type', 'application/json');
            $response->setContent($content);

            return $response;
        }

        $show_private_decks = /*$user->getIsShareDecks() ||*/ $user->getId() == $this->currentUser()->getId();

        /* @var $decklists \AppBundle\Entity\Decklist[] */
        $decklists = $this->decklistRepository->findBy(['user' => $user], ['dateCreation' => 'DESC', 'id' => 'DESC']);

        foreach($decklists as &$decklist) {
            $decklist->setDescriptionMd('');
        }

        if ($show_private_decks) {
            /* @var $decks \AppBundle\Entity\Deck[] */
            $decks = $this->deckRepository->findBy(['user' => $user], ['dateCreation' => 'DESC', 'id' => 'DESC']);

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
	/**
	 * @param mixed $id
	 * @return \Symfony\Component\HttpFoundation\Response
	 */
	public function loadDeckAction($id, Request $request) {
		$response = new Response();

        /* @var $em \Doctrine\ORM\EntityManager */
        $em = $this->getDoctrine()->getManager();

        /* @var $deck \AppBundle\Entity\Deck */
		$deck = $this->deckRepository->find($id);

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
