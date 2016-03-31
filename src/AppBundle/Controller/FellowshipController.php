<?php
namespace AppBundle\Controller;

use AppBundle\Entity\Fellowship;
use AppBundle\Entity\FellowshipDeck;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

class FellowshipController extends Controller {

    public function newAction($deck1_id, $deck2_id, $deck3_id, $deck4_id) {
        $response = new Response();

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

        return $this->render('AppBundle:Quest:fellowshipedit.html.twig', [
            'pagetitle' => "Create a Fellowship",
            'deck1' => $decks[0],
            'deck2' => $decks[1],
            'deck3' => $decks[2],
            'deck4' => $decks[3],
        ], $response);
    }

    public function editAction($fellowship_id) {
        $response = new Response();

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

        /* @var $fellowship_decks \AppBundle\Entity\FellowshipDeck[] */
        $fellowship_decks = $fellowship->getDecks();
        $data = [
            'pagetitle' => "Edit Fellowship",
            'deck1' => null,
            'deck2' => null,
            'deck3' => null,
            'deck4' => null,
            'fellowship' => $fellowship
        ];

        foreach ($fellowship_decks as $fellowship_deck) {
            $data['deck' . $fellowship_deck->getDeckNumber()] = $fellowship_deck->getDeck();
        }


        return $this->render('AppBundle:Quest:fellowshipedit.html.twig', $data, $response);
    }

    public function viewAction($fellowship_id) {
        /* @var $fellowship \AppBundle\Entity\Fellowship */
        $fellowship = $this->getDoctrine()->getManager()->getRepository('AppBundle:Fellowship')->find($fellowship_id);

        if (!$fellowship) {
            return $this->render('AppBundle:Default:error.html.twig', [
                'pagetitle' => "Error",
                'error' => "This Fellowship doesn't exist."
            ]);
        }

        $is_owner = $this->getUser() && $this->getUser()->getId() == $fellowship->getUser()->getId();
        if (!$fellowship->getUser()->getIsShareDecks() && !$is_owner) {
            return $this->render('AppBundle:Default:error.html.twig', [
                'pagetitle' => "Error",
                'error' => 'You are not allowed to view this fellowship. To get access, you can ask the fellowship owner to enable "Share my decks" on their account.'
            ]);
        }

        /* @var $fellowship_decks \AppBundle\Entity\FellowshipDeck[] */
        $fellowship_decks = $fellowship->getDecks();
        $data = [
            'pagetitle' => "Fellowship",
            'fellowship' => $fellowship,
            'deck1' => null,
            'deck2' => null,
            'deck3' => null,
            'deck4' => null,
            'is_owner' => $is_owner,
        ];

        foreach ($fellowship_decks as $fellowship_deck) {
            $data['deck' . $fellowship_deck->getDeckNumber()] = $fellowship_deck->getDeck();
        }

        return $this->render('AppBundle:Quest:fellowshipview.html.twig', $data);
    }

    public function saveAction(Request $request) {
        /* @var $em \Doctrine\ORM\EntityManager */
        $em = $this->getDoctrine()->getManager();

        /* @var $user \AppBundle\Entity\User */
        $user = $this->getUser();

        $fellowship_id = intval(filter_var($request->request->get('fellowship_id'), FILTER_SANITIZE_NUMBER_INT));

        if ($fellowship_id) {
            /* @var $fellowship \AppBundle\Entity\Fellowship */
            $fellowship = $em->getRepository('AppBundle:Fellowship')->find($fellowship_id);

            if (!$fellowship) {
                throw $this->createNotFoundException("This Fellowship does not exists.");
            }

            if ($user->getId() !== $fellowship->getUser()->getId()) {
                throw $this->createAccessDeniedException("Access denied to this object.");
            }

            foreach ($fellowship->getDecks() as $deck) {
                $fellowship->removeDeck($deck);
                $em->remove($deck);
            }
        } else {
            $fellowship = new Fellowship();
            $fellowship->setIsPublic(false);
            $fellowship->setNbVotes(0);
            $fellowship->setNbComments(0);
            $fellowship->setNbFavorites(0);
        }

        $name = trim(filter_var($request->request->get('name'), FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES));
        $name = substr($name, 0, 60);
        if (empty($name)) {
            $name = "Untitled Fellowship";
        }

        $descriptionMd = trim($request->request->get('descriptionMd'));
        $descriptionHtml = $this->get('texts')->markdown($descriptionMd);

        $fellowship->setUser($user);
        $fellowship->setName($name);
        $fellowship->setNameCanonical($this->get('texts')->slugify($name));
        $fellowship->setDescriptionMd($descriptionMd);
        $fellowship->setDescriptionHtml($descriptionHtml);

        for ($i = 1; $i <= 4; $i++) {
            $deck_id = intval(filter_var($request->request->get("deck".$i."_id"), FILTER_SANITIZE_NUMBER_INT));

            if ($deck_id) {
                /* @var $deck \AppBundle\Entity\Deck */
                $deck = $em->getRepository('AppBundle:Deck')->find($deck_id);

                if (!$deck) {
                    throw $this->createNotFoundException("A selected deck does not exists.");
                }

                $deck_user = $deck->getUser();
                if (!$deck_user->getIsShareDecks() && $user->getId() != $deck_user->getId()) {
                    throw $this->createAccessDeniedException('You are not allowed to view this deck. To get access, you can ask the deck owner to enable "Share my decks" on their account.');
                }

                $fellowship_deck = new FellowshipDeck();
                $fellowship_deck->setDeck($deck);
                $fellowship_deck->setDeckNumber($i);
                $fellowship_deck->setFellowship($fellowship);

                $fellowship->addDeck($fellowship_deck);
            }
        }

        $em->persist($fellowship);
        $em->flush();

        return $this->redirect($this->generateUrl('fellowship_view', [
            'fellowship_id' => $fellowship->getId()
        ]));
    }
}
