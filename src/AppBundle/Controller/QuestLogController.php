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

    public function newWithFellowshipAction($fellowship_id) {
        /* @var $user \AppBundle\Entity\User */
        $user = $this->getUser();

        /* @var $fellowship \AppBundle\Entity\Fellowship */
        $fellowship = $this->getDoctrine()->getRepository('AppBundle:Fellowship')->find($fellowship_id);

        if (!$fellowship) {
            throw $this->createNotFoundException("This fellowship does not exists.");
        }

        if ($fellowship->getUser()->getIsShareDecks() && $user->getId() !== $fellowship->getUser()->getId()) {
            throw $this->createAccessDeniedException("Access denied to this object.");
        }

        $quests = $this->getDoctrine()->getRepository('AppBundle:Scenario')->findBy([], ['position' => 'ASC']);


        /* @var $fellowship_decks \AppBundle\Entity\FellowshipDeck[] */
        $fellowship_decks = $fellowship->getDecks();
        $data = [
            'deck1_id' => null,
            'deck2_id' => null,
            'deck3_id' => null,
            'deck4_id' => null,
        ];

        foreach ($fellowship_decks as $fellowship_deck) {
            $data['deck' . $fellowship_deck->getDeckNumber() . '_id'] = $fellowship_deck->getDeck()->getId();
        }

        return $this->redirect($this->generateUrl('questlog_new', $data));
    }

    public function newAction($deck1_id, $deck2_id, $deck3_id, $deck4_id) {
        $response = new Response();

        $quests = $this->getDoctrine()->getRepository('AppBundle:Scenario')->findBy([], ['position' => 'ASC']);

        $decks = [];
        $deck_ids = func_get_args();

        for ($i = 0; $i < 4; $i++) {
            $decks[$i] = null;

            if ($deck_ids[$i]) {
                $decks[$i] = $this->getDoctrine()->getManager()->getRepository('AppBundle:Deck')->find($deck_ids[$i]);

                if ($decks[$i]) {
                    $user = $decks[$i]->getUser();

                    if (!$user->getIsShareDecks() && $user->getId() != $this->getUser()->getId()) {
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
