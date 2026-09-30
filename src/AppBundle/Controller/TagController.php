<?php
namespace AppBundle\Controller;

use AppBundle\Repository\DeckRepository;
use AppBundle\Services\Decks;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Entity\Deck;
use Symfony\Component\HttpFoundation\Response;

class TagController extends AbstractController {
    use CurrentUserTrait;

    /**
     * @var Decks
     */
    private $decks;

    /**
     * @var DeckRepository
     */
    private $deckRepository;

    public function __construct(Decks $decks, DeckRepository $deckRepository) {
        $this->decks = $decks;
        $this->deckRepository = $deckRepository;
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function addAction(Request $request) {
        $list_id = $request->get('ids');
        $list_tag = $this->decks->normalizeTags((array) $request->get('tags'));

        /* @var $em \Doctrine\ORM\EntityManager */
        $em = $this->getDoctrine()->getManager();

        $response = ["success" => true];

        foreach ($list_id as $id) {
            /* @var $deck Deck */
            $deck = $this->deckRepository->find($id);

            if (!$deck) {
                continue;
            }

            if ($this->currentUser()->getId() != $deck->getUser()->getId()) {
                continue;
            }

            $tags = $this->decks->normalizeTags(array_merge($this->decks->normalizeTags($deck->getTags()), $list_tag));
            $response['tags'][$deck->getId()] = $tags;
            $deck->setTags(implode(' ', $tags));
        }
        $em->flush();

        return new Response(json_encode($response));
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function removeAction(Request $request) {
        $list_id = $request->get('ids');
        $list_tag = $this->decks->normalizeTags((array) $request->get('tags'));

        /* @var $em \Doctrine\ORM\EntityManager */
        $em = $this->getDoctrine()->getManager();

        $response = ["success" => true];

        foreach ($list_id as $id) {
            /* @var $deck Deck */
            $deck = $this->deckRepository->find($id);

            if (!$deck) {
                continue;
            }

            if ($this->currentUser()->getId() != $deck->getUser()->getId()) {
                continue;
            }

            $tags = array_values(array_diff($this->decks->normalizeTags($deck->getTags()), $list_tag));
            $response['tags'][$deck->getId()] = $tags;
            $deck->setTags(implode(' ', $tags));
        }
        $em->flush();

        return new Response(json_encode($response));
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function clearAction(Request $request) {
        $list_id = $request->get('ids');

        /* @var $em \Doctrine\ORM\EntityManager */
        $em = $this->getDoctrine()->getManager();

        $response = ["success" => true];

        foreach ($list_id as $id) {
            /* @var $deck Deck */
            $deck = $this->deckRepository->find($id);

            if (!$deck) {
                continue;
            }

            if ($this->currentUser()->getId() != $deck->getUser()->getId()) {
                continue;
            }

            $response['tags'][$deck->getId()] = [];
            $deck->setTags('');
        }
        $em->flush();

        return new Response(json_encode($response));
    }
}