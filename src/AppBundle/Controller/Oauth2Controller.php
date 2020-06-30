<?php

namespace AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;

class Oauth2Controller extends Controller {
	/**
	 * Get the description of all the Decks of the authenticated user
	 *
	 * @ApiDoc(
	 *  section="Deck",
	 *  resource=true,
	 *  description="All the Decks",
	 * )
	 * @param Request $request
	 */
	public function listDecksAction(Request $request) {
		$response = new Response();
		$response->headers->add(['Access-Control-Allow-Origin' => '*']);

		/* @var $decks \AppBundle\Entity\Deck[] */
		$decks = $this->getDoctrine()->getRepository('AppBundle:Deck')->findBy(['user' => $this->getUser()]);

		$dateUpdates = array_map(function($deck) {
			return $deck->getDateUpdate();
		}, $decks);

		$response->setLastModified(max($dateUpdates));
		if ($response->isNotModified($request)) {
			return $response;
		}

		$content = json_encode($decks);

		$response->headers->set('Content-Type', 'application/json');
		$response->setContent($content);

		return $response;
	}

	/**
	 * Get the description of one Deck of the authenticated user
	 *
	 * @ApiDoc(
	 *  section="Deck",
	 *  resource=true,
	 *  description="Load One Deck",
	 *  requirements={
	 *      {
	 *          "name"="id",
	 *          "dataType"="integer",
	 *          "requirement"="\d+",
	 *          "description"="The numeric identifier of the Deck to load"
	 *      },
	 *  },
	 * )
	 * @param Request $request
	 */
	public function loadDeckAction($id) {
		$response = new Response();
		$response->headers->add(['Access-Control-Allow-Origin' => '*']);

		/* @var $deck \AppBundle\Entity\Deck */
		$deck = $this->getDoctrine()->getRepository('AppBundle:Deck')->find($id);

		// if ($deck->getUser()->getId() !== $this->getUser()->getId()) {
		// 	throw $this->createAccessDeniedException("Access denied to this object.");
		// }

		// $response->setLastModified($deck->getDateUpdate());
		// if ($response->isNotModified($request)) {
		// 	return $response;
		// }

		if (!$deck) {
		   $content = json_encode([
                   	      'success' => false,
                	      'error' => 'Deck not found.'
			    ]);
            	   $response->headers->set('Content-Type', 'application/json');
		   $response->setContent($content);

            	   return $response;				   
		}

		$user = $deck->getUser();
		if (!$user->getIsShareDecks()) {
		   $content = json_encode([
                   	      'success' => false,
                	      'error' => 'You are not allowed to view this deck. To get access, you can ask the deck owner to enable "Share my decks" on their account.'
			    ]);
		   $response->headers->set('Content-Type', 'application/json');
            	   $response->setContent($content);

            	   return $response;
		}


		$content = json_encode($deck);

		$response->headers->set('Content-Type', 'application/json');
		$response->setContent($content);

		return $response;
	}


	/**
	 * Save one Deck of the authenticated user. The parameters are the same as in the response to the load method, but only a few are writable.
	 * So you can parse the result from the load, change a few values, then send the object as the param of an ajax request.
	 * If successful, id of Deck is in the msg
	 *
	 * @ApiDoc(
	 *  section="Deck",
	 *  resource=true,
	 *  description="Save One Deck",
	 *  requirements={
	 *      {
	 *          "name"="id",
	 *          "dataType"="integer",
	 *          "requirement"="\d+",
	 *          "description"="The numeric identifier of the Deck to load ; 0 to create a new Deck"
	 *      },
	 *  },
	 *  parameters={
	 *      {"name"="name", "dataType"="string", "required"=true, "description"="Name of the Deck"},
	 *      {"name"="decklist_id", "dataType"="integer", "required"=false, "description"="Identifier of the Decklist from which the Deck is copied"},
	 *      {"name"="description_md", "dataType"="string", "required"=false, "description"="Description of the Decklist in Markdown"},
	 *      {"name"="tags", "dataType"="string", "required"=false, "description"="Space-separated list of tags"},
	 *      {"name"="slots", "dataType"="string", "required"=true, "description"="Content of the Decklist as a JSON object"},
	 *  },
	 * )
	 * @param Request $request
	 */
	//public function saveDeckAction($id, Request $request)
	//{
	//	/* @var $deck \AppBundle\Entity\Deck */
	//
	//	if(!$id)
	//	{
	//		$deck = new Deck();
	//		$this->getDoctrine()->getManager()->persist($deck);
	//	}
	//	else
	//	{
	//		$deck = $this->getDoctrine()->getRepository('AppBundle:Deck')->find($id);
	//		if($deck->getUser()->getId() !== $this->getUser()->getId())
	//		{
	//			throw $this->createAccessDeniedException("Access denied to this object.");
	//		}
	//	}
	//
	//	$slots = (array) json_decode($request->get('slots'));
	//	if (!count($slots)) {
	//		return new JsonResponse([
	//				'success' => FALSE,
	//				'msg' => "Slots missing"
	//		]);
	//	}
	//	foreach($slots as $card_code => $qty)
	//	{
	//		if(!is_string($card_code) || !is_integer($qty))
	//		{
	//			return new JsonResponse([
	//					'success' => FALSE,
	//					'msg' => "Slots invalid"
	//			]);
	//		}
	//	}
	//
	//	$name = filter_var($request->get('name'), FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES);
	//	if(!$name) {
	//		return new JsonResponse([
	//				'success' => FALSE,
	//				'msg' => "Name missing"
	//		]);
	//	}
	//
	//	$decklist_id = filter_var($request->get('decklist_id'), FILTER_SANITIZE_NUMBER_INT);
	//	$description = trim($request->get('description'));
	//	$tags = filter_var($request->get('tags'), FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES);
	//
	//	$this->get('decks')->saveDeck($this->getUser(), $deck, $decklist_id, $name, $description, $tags, $slots, null);
	//
	//	$this->getDoctrine()->getManager()->flush();
	//
	//	return new JsonResponse([
	//			'success' => TRUE,
	//			'msg' => $deck->getId()
	//	]);
	//}

	/**
	 * Try to publish one Deck of the authenticated user
	 * If publication is successful, update the version of the deck and return the id of the decklist
	 *
	 * @ApiDoc(
	 *  section="Deck",
	 *  resource=true,
	 *  description="Publish One Deck",
	 *  requirements={
	 *      {
	 *          "name"="id",
	 *          "dataType"="integer",
	 *          "requirement"="\d+",
	 *          "description"="The numeric identifier of the Deck to publish"
	 *      },
	 *  },
	 *  parameters={
	 *      {"name"="description_md", "dataType"="string", "required"=false, "description"="Description of the Decklist in Markdown"},
	 *      {"name"="precedent_id", "dataType"="integer", "required"=false, "description"="Identifier of the Predecessor of the Decklist"},
	 *  },
	 * )
	 * @param Request $request
	 */
	public function publishDeckAction($id, Request $request) {
		throw $this->createAccessDeniedException("Publishing via API has been disabled.");		
	}
}
