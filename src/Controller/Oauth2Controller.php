<?php

namespace App\Controller;

use App\Repository\DeckRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

/**
 * External-integration deck endpoint (used by the DragnCards "Play on DragnCards"
 * links for private decks). Despite the legacy "oauth2" name it uses no OAuth: it is
 * anonymous and CORS-open, and only returns a deck when its owner has enabled
 * "Share my decks". Published decklists go through ApiPublicController instead.
 */
class Oauth2Controller extends AbstractController {

	/**
	 * @var DeckRepository
	 */
	private $deckRepository;

	public function __construct(DeckRepository $deckRepository) {
		$this->deckRepository = $deckRepository;
	}

	/**
	 * Return one Deck as JSON, if the owner shares their decks.
	 *
	 * @param mixed $id
	 * @return \Symfony\Component\HttpFoundation\Response
	 */
	public function loadDeckAction($id) {
		$response = new Response();
		$response->headers->set('Content-Type', 'application/json');
		$response->headers->add(['Access-Control-Allow-Origin' => '*']);

		/* @var $deck \App\Entity\Deck */
		$deck = $this->deckRepository->find($id);

		if (!$deck) {
			$response->setContent(json_encode([
				'success' => false,
				'error' => 'Deck not found.',
			]));

			return $response;
		}

		$user = $deck->getUser();
		if (!$user->getIsShareDecks()) {
			$response->setContent(json_encode([
				'success' => false,
				'error' => 'You are not allowed to view this deck. To get access, you can ask the deck owner to enable "Share my decks" on their account.',
			]));

			return $response;
		}

		$response->setContent(json_encode($deck));

		return $response;
	}
}
