<?php
namespace AppBundle\Controller;

use AppBundle\Entity\Questlog;
use AppBundle\Entity\QuestlogDeck;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

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

        $questlog = new Questlog();
        $questlog->setSuccess(true);

        return $this->render('AppBundle:Quest:edit.html.twig', [
            'quests' => $quests,
            'pagetitle' => "Log a Quest",
            'deck1' => $decks[0],
            'deck2' => $decks[1],
            'deck3' => $decks[2],
            'deck4' => $decks[3],
            'deck1_content' => null,
            'deck2_content' => null,
            'deck3_content' => null,
            'deck4_content' => null,
            'questlog' => $questlog,
            'is_public' => false
        ], $response);
    }

    public function editAction($questlog_id) {
        /* @var $em \Doctrine\ORM\EntityManager */
        $em = $this->getDoctrine()->getManager();


        $response = new Response();

        /* @var $user \AppBundle\Entity\User */
        $user = $this->getUser();

        /* @var $questlog \AppBundle\Entity\Questlog */
        $questlog = $em->getRepository('AppBundle:Questlog')->find($questlog_id);

        if (!$questlog) {
            throw new NotFoundHttpException("This questlog does not exists.");
        }

        if ($user->getId() !== $questlog->getUser()->getId()) {
            throw new AccessDeniedHttpException("Access denied to this object.");
        }

        /* @var $quests \AppBundle\Entity\Scenario[] */
        $quests = $em->getRepository('AppBundle:Scenario')->findBy([], ['position' => 'ASC']);

        $data = [
            'quests' => $quests,
            'pagetitle' => "Edit Questlog",
            'deck1' => null,
            'deck2' => null,
            'deck3' => null,
            'deck4' => null,
            'deck1_content' => null,
            'deck2_content' => null,
            'deck3_content' => null,
            'deck4_content' => null,
            'questlog' => $questlog,
            'is_public' => $questlog->getIsPublic()
        ];

        /* @var $questlog_decks \AppBundle\Entity\QuestlogDeck[] */
        $questlog_decks = $questlog->getDecks();
        foreach ($questlog_decks as $questlog_deck) {
            $data['deck' . $questlog_deck->getDeckNumber()] = $questlog_deck->getDeck() ?: $questlog_deck->getDecklist();
            $data['deck' . $questlog_deck->getDeckNumber() . '_content'] = $questlog_deck->getContent();
        }

        return $this->render('AppBundle:Quest:edit.html.twig', $data, $response);
    }

    public function saveAction(Request $request) {
        /* @var $em \Doctrine\ORM\EntityManager */
        $em = $this->getDoctrine()->getManager();

        /* @var $user \AppBundle\Entity\User */
        $user = $this->getUser();

        $questlog_id = intval(filter_var($request->request->get('questlog_id'), FILTER_SANITIZE_NUMBER_INT));

        if ($questlog_id) {
            /* @var $questlog \AppBundle\Entity\Questlog */
            $questlog = $em->getRepository('AppBundle:Questlog')->find($questlog_id);

            if (!$questlog) {
                throw new NotFoundHttpException("This questlog does not exists.");
            }

            if ($user->getId() !== $questlog->getUser()->getId()) {
                throw new AccessDeniedHttpException("Access denied to this object.");
            }
        } else {
            $questlog = new Questlog();
            $questlog->setIsPublic(false);
            $questlog->setNbVotes(0);
            $questlog->setNbComments(0);
            $questlog->setNbFavorites(0);
            $questlog->setNbDecks(0);
        }

        $name = trim(filter_var($request->request->get('name'), FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES));
        $name = substr($name, 0, 250);
        if (empty($name)) {
            $name = "Untitled Questlog";
        }

        $descriptionMd = trim($request->request->get('descriptionMd'));
        $descriptionHtml = $this->get('texts')->markdown($descriptionMd);

        $quest = intval(filter_var($request->request->get('quest'), FILTER_SANITIZE_NUMBER_INT));
        $date = trim(filter_var($request->request->get('date'), FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES));
        $difficulty = trim(filter_var($request->request->get('difficulty'), FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES));
        $victory = trim(filter_var($request->request->get('victory'), FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES));
        $score = intval(filter_var($request->request->get('score'), FILTER_SANITIZE_NUMBER_INT));
        $public = boolval(filter_var($request->request->get('public'), FILTER_SANITIZE_NUMBER_INT));

        $victory = ($victory == 'no' ? false : true);
        $difficulty = in_array($difficulty, ['normal', 'easy', 'nightmare']) ? $difficulty : 'normal';

        /* @var $scenario \AppBundle\Entity\Scenario */
        $scenario = $em->getRepository('AppBundle:Scenario')->find($quest);

        if (!$scenario) {
            throw new NotFoundHttpException("This scenario does not exists.");
        }

        $date = new \DateTime($date);


        $questlog->setUser($user);
        $questlog->setName($name);
        $questlog->setNameCanonical($this->get('texts')->slugify($name));
        $questlog->setDescriptionMd($descriptionMd);
        $questlog->setDescriptionHtml($descriptionHtml);
        $questlog->setScenario($scenario);
        $questlog->setDatePlayed($date);
        $questlog->setQuestMode($difficulty);
        $questlog->setSuccess($victory);
        $questlog->setScore($score);
        $questlog->setIsPublic($public);

        $is_public = $questlog->getIsPublic();

        if (!$is_public) {
            // Allow deck changing
            foreach ($questlog->getDecks() as $deck) {
                $questlog->removeDeck($deck);
                $em->remove($deck);
            }

            $nb_decks = 0;
            $skip = 0;
            for ($i = 1; $i <= 4; $i++) {
                $deck_id = intval(filter_var($request->request->get("deck".$i."_id"), FILTER_SANITIZE_NUMBER_INT));
                $is_decklist = filter_var($request->get("deck".$i."_is_decklist"), FILTER_SANITIZE_STRING) == 'true';

                if ($deck_id) {
                    if (!$is_decklist) {
                        /* @var $deck \AppBundle\Entity\Deck */
                        $deck = $em->getRepository('AppBundle:Deck')->find($deck_id);

                        if (!$deck) {
                            throw new NotFoundHttpException("One of the selected decks does not exists.");
                        }

                        $deck_user = $deck->getUser();
                        $is_owner = $user->getId() == $deck_user->getId();
                        if (!$is_owner && !$deck_user->getIsShareDecks()) {
                            throw new AccessDeniedHttpException('You are not allowed to view this deck. To get access, you can ask the deck owner to enable "Share my decks" on their account.');
                        }

                        if (!$is_owner) {
                            $deck = $this->get('decks')->cloneDeck($deck, $user);
                        }

                        $content = (array) json_decode($request->get("deck".$i."_content"));

                        if (!isset($content['main']) || !count($content['main'])) {
                            return new Response('Cannot save a questlog with an empty deck');
                        }

                        $questlog_deck = new QuestlogDeck();
                        $questlog_deck->setDeck($deck);
                        $questlog_deck->setContent(json_encode($content));
                        $questlog_deck->setDeckNumber($i - $skip);
                        $questlog_deck->setQuestlog($questlog);

                        $questlog->addDeck($questlog_deck);
                    } else {
                        /* @var $decklist \AppBundle\Entity\Decklist */
                        $decklist = $em->getRepository('AppBundle:Decklist')->find($deck_id);

                        if (!$decklist) {
                            throw new NotFoundHttpException("One of the selected decks does not exists.");
                        }

                        $content = (array) json_decode($request->get("deck".$i."_content"));

                        if (!isset($content['main']) || !count($content['main'])) {
                            return new Response('Cannot save a questlog with an empty deck');
                        }

                        $questlog_decklist = new QuestlogDeck();
                        $questlog_decklist->setDecklist($decklist);
                        $questlog_decklist->setContent(json_encode($content));
                        $questlog_decklist->setDeckNumber($i - $skip);
                        $questlog_decklist->setQuestlog($questlog);

                        $questlog->addDeck($questlog_decklist);
                    }
                    $nb_decks++;
                } else {
                    $skip++;
                }
            }
            if ($nb_decks == 0) {
                throw new UnprocessableEntityHttpException("You can't save an empty questlog.");
            }

            $questlog->setNbDecks($nb_decks);
        }

        if ($public) {
            $questlog->setIsPublic(true);
            $questlog->setDatePublish(new \DateTime());
        }

        $em->persist($questlog);
        $em->flush();

        return $this->redirect($this->generateUrl('questlog_edit', [
            'questlog_id' => $questlog->getId()
        ]));
    }
}
