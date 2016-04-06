<?php
namespace AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class QuestLogController extends Controller {

    public function newAction($deck1_id, $deck2_id, $deck3_id, $deck4_id, Request $request) {
        /* @var $em \Doctrine\ORM\EntityManager */
        $em = $this->getDoctrine()->getManager();

        $response = new Response();

        /* @var $quests \AppBundle\Entity\Scenario[] */
        $quests = $em->getRepository('AppBundle:Scenario')->findBy([], ['position' => 'ASC']);

        $decks = [];
        $deck_ids = func_get_args();


        for ($i = 0; $i < 4; $i++) {
            $decks[$i] = null;

            if ($deck_ids[$i]) {
                $public = filter_var($request->get('p'.($i + 1)), FILTER_SANITIZE_NUMBER_INT);

                if ($public) {
                    $decks[$i] = $em->getRepository('AppBundle:Decklist')->find($deck_ids[$i]);
                } else {
                    $decks[$i] = $em->getRepository('AppBundle:Deck')->find($deck_ids[$i]);
                }

                if ($decks[$i]) {
                    $user = $decks[$i]->getUser();

                    if (!$public && !$user->getIsShareDecks() && $user->getId() != $this->getUser()->getId()) {
                        $decks[$i] = null;
                    }
                }
            }
        }

        return $this->render('AppBundle:Quest:questedit.html.twig', [
            'quests' => $quests,
            'pagetitle' => "Log a Quest",
            'deck1' => $decks[0],
            'deck2' => $decks[1],
            'deck3' => $decks[2],
            'deck4' => $decks[3],
        ], $response);
    }
}
