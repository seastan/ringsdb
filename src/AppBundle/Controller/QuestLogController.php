<?php
namespace AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use AppBundle\Entity\Deck;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Entity\Deckchange;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class QuestLogController extends Controller {

    public function newAction($deck1_id, $deck2_id, $deck3_id, $deck4_id) {
        $response = new Response();

        $quests = $this->getDoctrine()->getRepository('AppBundle:Scenario')->findBy([], ['position' => 'ASC']);

        $deck1 = null;
        if ($deck1_id) {
            $deck1 = $this->getDoctrine()->getManager()->getRepository('AppBundle:Deck')->find($deck1_id);
        }

        $deck2 = null;
        if ($deck2_id) {
            $deck2 = $this->getDoctrine()->getManager()->getRepository('AppBundle:Deck')->find($deck2_id);
        }

        $deck3 = null;
        if ($deck3_id) {
            $deck3 = $this->getDoctrine()->getManager()->getRepository('AppBundle:Deck')->find($deck3_id);
        }

        $deck4 = null;
        if ($deck4_id) {
            $deck4 = $this->getDoctrine()->getManager()->getRepository('AppBundle:Deck')->find($deck4_id);
        }

        return $this->render('AppBundle:QuestLog:questedit.html.twig', [
            'quests' => $quests,
            'pagetitle' => "Log a Quest",
            'deck1' => $deck1,
            'deck2' => $deck2,
            'deck3' => $deck3,
            'deck4' => $deck4,
        ], $response);
    }
}
