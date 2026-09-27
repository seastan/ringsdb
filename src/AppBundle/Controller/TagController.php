<?php
namespace AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Entity\Deck;
use Symfony\Component\HttpFoundation\Response;

class TagController extends Controller {
    public function addAction(Request $request) {
        $list_id = $request->get('ids');
        $list_tag = $this->get('decks')->normalizeTags((array) $request->get('tags'));

        /* @var $em \Doctrine\ORM\EntityManager */
        $em = $this->getDoctrine()->getManager();

        $response = ["success" => true];

        foreach ($list_id as $id) {
            /* @var $deck Deck */
            $deck = $em->getRepository('AppBundle:Deck')->find($id);

            if (!$deck) {
                continue;
            }

            if ($this->getUser()->getId() != $deck->getUser()->getId()) {
                continue;
            }

            $tags = $this->get('decks')->normalizeTags(array_merge($this->get('decks')->normalizeTags($deck->getTags()), $list_tag));
            $response['tags'][$deck->getId()] = $tags;
            $deck->setTags(implode(' ', $tags));
        }
        $em->flush();

        return new Response(json_encode($response));
    }

    public function removeAction(Request $request) {
        $list_id = $request->get('ids');
        $list_tag = $this->get('decks')->normalizeTags((array) $request->get('tags'));

        /* @var $em \Doctrine\ORM\EntityManager */
        $em = $this->getDoctrine()->getManager();

        $response = ["success" => true];

        foreach ($list_id as $id) {
            /* @var $deck Deck */
            $deck = $em->getRepository('AppBundle:Deck')->find($id);

            if (!$deck) {
                continue;
            }

            if ($this->getUser()->getId() != $deck->getUser()->getId()) {
                continue;
            }

            $tags = array_values(array_diff($this->get('decks')->normalizeTags($deck->getTags()), $list_tag));
            $response['tags'][$deck->getId()] = $tags;
            $deck->setTags(implode(' ', $tags));
        }
        $em->flush();

        return new Response(json_encode($response));
    }

    public function clearAction(Request $request) {
        $list_id = $request->get('ids');

        /* @var $em \Doctrine\ORM\EntityManager */
        $em = $this->getDoctrine()->getManager();

        $response = ["success" => true];

        foreach ($list_id as $id) {
            /* @var $deck Deck */
            $deck = $em->getRepository('AppBundle:Deck')->find($id);

            if (!$deck) {
                continue;
            }

            if ($this->getUser()->getId() != $deck->getUser()->getId()) {
                continue;
            }

            $response['tags'][$deck->getId()] = [];
            $deck->setTags('');
        }
        $em->flush();

        return new Response(json_encode($response));
    }
}