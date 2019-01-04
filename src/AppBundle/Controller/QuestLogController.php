<?php
namespace AppBundle\Controller;

use AppBundle\Entity\Questlog;
use AppBundle\Entity\QuestlogComment;
use AppBundle\Entity\QuestlogDeck;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use DateTime;

class QuestLogController extends Controller {

    public function mylistAction($scenario_name_canonical, $quest_mode) {
        /* @var $em \Doctrine\ORM\EntityManager */
        $em = $this->getDoctrine()->getManager();

        /* @var $quests \AppBundle\Entity\Scenario[] */
        $quests = $em->getRepository('AppBundle:Scenario')->findBy([], ['position' => 'ASC']);

        /* @var $user \AppBundle\Entity\User */
        $user = $this->getUser();
        $user = $this->getUser();
        if (!$user) {
            throw new AccessDeniedHttpException("You must be logged in for this operation.");
        }

        // Count played scenarios
        $playedEasy = [];
        $playedNormal = [];
        $playedNightmare = [];

        $dbh = $em->getConnection();
        $played = $dbh->executeQuery("SELECT DISTINCT scenario_id, quest_mode, sum(success) as victory FROM questlog WHERE user_id = ? GROUP BY scenario_id, quest_mode", [$user->getId()])->fetchAll(\PDO::FETCH_NAMED);

        foreach ($played as $c) {
            if ($c['quest_mode'] == 'easy') {
                $playedEasy[$c['scenario_id']] = $c['victory'];
            } elseif ($c['quest_mode'] == 'normal') {
                $playedNormal[$c['scenario_id']] = $c['victory'];
            } elseif ($c['quest_mode'] == 'nightmare') {
                $playedNightmare[$c['scenario_id']] = $c['victory'];
            }
        }

        if (count($played) == 0) {
            return $this->render('AppBundle:QuestLog:no-questlogs.html.twig', [
                'pagetitle' => "My Quest Logs",
                'pagedescription' => "Log a new quest."
            ]);
        } else {
            if ($scenario_name_canonical == null) {
                $res = $dbh->executeQuery("SELECT q.scenario_id, s.name_canonical, q.quest_mode
                      FROM questlog q
                      INNER JOIN scenario s ON q.scenario_id = s.id
                      WHERE q.user_id = ?
                      ORDER BY q.scenario_id LIMIT 1", [$user->getId()])->fetch(\PDO::FETCH_NUM);
                $scenario_name_canonical = $res[1];
                $quest_mode = $res[2];
            }

            if ($quest_mode != 'easy' && $quest_mode != 'nightmare') {
                $quest_mode = 'normal';
            }

            /* @var $scenario \AppBundle\Entity\Scenario */
            $scenario = $em->getRepository('AppBundle:Scenario')->findOneBy(['nameCanonical' => $scenario_name_canonical]);

            if ($scenario == null) {
                throw new NotFoundHttpException("This quest does not exists.");
            }

            /* @var $questlogs \AppBundle\Entity\Questlog[] */
            $questlogs = $em->getRepository('AppBundle:Questlog')->findBy(['user' => $user, 'scenario' => $scenario, 'questMode' => $quest_mode], ['dateCreation' => 'DESC']);

            $victories = 0;
            $defeats = 0;
            $total = count($questlogs);
            foreach ($questlogs as $questlog) {
                if ($questlog->getSuccess()) {
                    $victories++;
                } else {
                    $defeats++;
                }
            }

            return $this->render('AppBundle:QuestLog:my-questlogs.html.twig', [
                'pagetitle' => "My Quest Logs",
                'pagedescription' => "Log a new quest.",
                'quests' => $quests,
                'played_easy' => $playedEasy,
                'played_normal' => $playedNormal,
                'played_nightmare' => $playedNightmare,
                'questlogs' => $questlogs,
                'quest_mode' => $quest_mode,
                'selected_scenario' => $scenario,
                'victories' => $victories,
                'defeats' => $defeats,
                'total' => $total,
                'ratio' => ($total ? sprintf("%.0f%%", 100 * $victories / $total) : '-'),
                'compact' => false
            ]);
        }
    }


    public function myCompleteListAction() {
        /* @var $em \Doctrine\ORM\EntityManager */
        $em = $this->getDoctrine()->getManager();

        /* @var $quests \AppBundle\Entity\Scenario[] */
        $quests = $em->getRepository('AppBundle:Scenario')->findBy([], ['position' => 'ASC']);

        /* @var $user \AppBundle\Entity\User */
        $user = $this->getUser();

        // Count played scenarios
        $playedEasy = [];
        $playedNormal = [];
        $playedNightmare = [];

        $dbh = $em->getConnection();
        $played = $dbh->executeQuery("SELECT DISTINCT scenario_id, quest_mode, sum(success) as victory FROM questlog WHERE user_id = ? GROUP BY scenario_id, quest_mode", [$user->getId()])->fetchAll(\PDO::FETCH_NAMED);

        foreach ($played as $c) {
            if ($c['quest_mode'] == 'easy') {
                $playedEasy[$c['scenario_id']] = $c['victory'];
            } elseif ($c['quest_mode'] == 'normal') {
                $playedNormal[$c['scenario_id']] = $c['victory'];
            } elseif ($c['quest_mode'] == 'nightmare') {
                $playedNightmare[$c['scenario_id']] = $c['victory'];
            }
        }

        /* @var $questlogs \AppBundle\Entity\Questlog[] */
        $questlogs = $em->getRepository('AppBundle:Questlog')->findBy(['user' => $user], ['dateCreation' => 'DESC']);

        return $this->render('AppBundle:QuestLog:my-questlogs.html.twig', [
            'pagetitle' => "My Quest Logs",
            'pagedescription' => "Log a new quest.",
            'quests' => $quests,
            'played_easy' => $playedEasy,
            'played_normal' => $playedNormal,
            'played_nightmare' => $playedNightmare,
            'questlogs' => $questlogs,
            'quest_mode' => 'normal',
            'compact' => true
        ]);
    }


    public function listAction($type, $page = 1, Request $request) {
        $response = new Response();
        $response->setPublic();
        $response->setMaxAge($this->container->getParameter('cache_expiration'));

        /**
         * @var $questlog_manager \AppBundle\Model\QuestLogManager
         */
        $questlog_manager = $this->get('questlog_manager');
        $questlog_manager->setLimit(30);
        $questlog_manager->setPage($page);

        $header = '';

        switch ($type) {
            case 'find':
                $pagetitle = "Quest Log search results";
                $header = $this->searchForm($request);
                $paginator = $questlog_manager->findQuestLogsWithComplexSearch();
                break;

            case 'favorites':
                $response->setPrivate();
                $user = $this->getUser();
                if ($user) {
                    $paginator = $questlog_manager->findQuestLogsByFavorite($user);
                } else {
                    $paginator = $questlog_manager->getEmptyList();
                }
                $pagetitle = "Favorite Quest Logs";
                break;

            case 'mine':
                $response->setPrivate();
                $user = $this->getUser();
                if ($user) {
                    $paginator = $questlog_manager->findQuestLogsByAuthor($user);
                } else {
                    $paginator = $questlog_manager->getEmptyList();
                }
                $pagetitle = "My Public Quest Logs";
                break;

            case 'recent':
                $paginator = $questlog_manager->findQuestLogsByAge(false);
                $pagetitle = "Recent Quest Logs";
                break;

            case 'halloffame':
                $paginator = $questlog_manager->findQuestLogsInHallOfFame();
                $pagetitle = "Hall of Fame";
                break;

            case 'hottopics':
                $paginator = $questlog_manager->findQuestLogsInHotTopic();
                $pagetitle = "Hot Topics";
                break;

            case 'popular':
            default:
                $paginator = $questlog_manager->findQuestLogsByPopularity();
                $pagetitle = "Popular Quest Logs";
                break;
        }

        return $this->render('AppBundle:QuestLog:public-questlogs.html.twig', [
            'pagetitle' => $pagetitle,
            'pagedescription' => "Browse the collection of thousands of premade decks.",
            'questlogs' => $paginator,
            'url' => $request->getRequestUri(),
            'header' => $header,
            'type' => $type,
            'pages' => $questlog_manager->getClosePages(),
            'prevurl' => $questlog_manager->getPreviousUrl(),
            'nexturl' => $questlog_manager->getNextUrl(),
        ], $response);
    }

    public function newAction($deck1_id, $deck2_id, $deck3_id, $deck4_id, $public) {
        /* @var $em \Doctrine\ORM\EntityManager */
        $em = $this->getDoctrine()->getManager();

        $response = new Response();

        /* @var $quests \AppBundle\Entity\Scenario[] */
        $quests = $em->getRepository('AppBundle:Scenario')->findBy([], ['position' => 'ASC']);

        /* @var $decks \AppBundle\Entity\Deck[] */
        $decks = [];
        $deck_ids = func_get_args();

        for ($i = 0; $i < 4; $i++) {
            $decks[$i] = null;
            
            if ($deck_ids[$i]) {
                /* $public = filter_var($request->get('p'.($i + 1)), FILTER_SANITIZE_NUMBER_INT); */
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

        return $this->render('AppBundle:QuestLog:edit.html.twig', [
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
            'deck1_player_name' => 'Seastan',
            'deck2_player_name' => null,
            'deck3_player_name' => null,
            'deck4_player_name' => null,
            'questlog' => $questlog,
            'is_locked_as_public' => false
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

        $is_locked_as_public = ($questlog->getNbVotes() > 0 || $questlog->getNbFavorites() > 0 || $questlog->getNbComments() > 0);

        $data = [
            'quests' => $quests,
            'pagetitle' => "Edit Quest Log",
            'deck1' => null,
            'deck2' => null,
            'deck3' => null,
            'deck4' => null,
            'deck1_content' => null,
            'deck2_content' => null,
            'deck3_content' => null,
            'deck4_content' => null,
            'deck1_player_name' => null,
            'deck2_player_name' => null,
            'deck3_player_name' => null,
            'deck4_player_name' => null,
            'questlog' => $questlog,
            'is_locked_as_public' => $is_locked_as_public
        ];

        /* @var $questlog_decks \AppBundle\Entity\QuestlogDeck[] */
        $questlog_decks = $questlog->getDecks();
        foreach ($questlog_decks as $questlog_deck) {
            $data['deck' . $questlog_deck->getDeckNumber()] = $questlog_deck->getDecklist() ?: $questlog_deck->getDeck();
            $data['deck' . $questlog_deck->getDeckNumber() . '_content'] = $questlog_deck->getContent();
            $data['deck' . $questlog_deck->getDeckNumber() . '_player_name'] = $questlog_deck->getPlayer();
        }

        return $this->render('AppBundle:QuestLog:edit.html.twig', $data, $response);
    }

    public function viewAction($questlog_id) {
        /* @var $questlog \AppBundle\Entity\Questlog */
        $questlog = $this->getDoctrine()->getManager()->getRepository('AppBundle:Questlog')->find($questlog_id);

        if (!$questlog) {
            throw new NotFoundHttpException("This questlog does not exists.");
        }

        $is_owner = $this->getUser() && $this->getUser()->getId() == $questlog->getUser()->getId();
        $is_public = $questlog->getIsPublic();

        if (!$questlog->getUser()->getIsShareDecks() && !$is_owner && !$is_public) {
            throw new AccessDeniedHttpException('You are not allowed to view this questlog. To get access, you can ask it\'s owner to enable "Share my decks" on their account.');
        }

        if ($is_public) {
            $commenters = array_map(function($comment) {
                /* @var $comment \AppBundle\Entity\QuestlogComment */
                return $comment->getUser()->getUsername();
            }, $questlog->getComments()->getValues());

            $commenters[] = $questlog->getUser()->getUsername();
        } else {
            $commenters = [];
        }

        $data = [
            'pagetitle' => $questlog->getScenario()->getName() . " - Quest Log",
            'deck1' => null,
            'deck2' => null,
            'deck3' => null,
            'deck4' => null,
            'deck1_content' => null,
            'deck2_content' => null,
            'deck3_content' => null,
            'deck4_content' => null,
            'deck1_player_name' => null,
            'deck2_player_name' => null,
            'deck3_player_name' => null,
            'deck4_player_name' => null,
            'questlog' => $questlog,
            'is_owner' => $is_owner,
            'is_public' => $is_public,
            'commenters' => $commenters
        ];

        /* @var $questlog_decks \AppBundle\Entity\QuestlogDeck[] */
        $questlog_decks = $questlog->getDecks();
        foreach ($questlog_decks as $questlog_deck) {
            $data['deck' . $questlog_deck->getDeckNumber()] = $questlog_deck->getDecklist() ?: $questlog_deck->getDeck();
            $data['deck' . $questlog_deck->getDeckNumber() . '_content'] = $questlog_deck->getContent();
            $data['deck' . $questlog_deck->getDeckNumber() . '_player_name'] = $questlog_deck->getPlayer();
        }

        return $this->render('AppBundle:QuestLog:view.html.twig', $data);
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

        $is_locked_as_public = ($questlog->getNbVotes() > 0 || $questlog->getNbFavorites() > 0 || $questlog->getNbComments() > 0);
        if (!$is_locked_as_public) {
            // Allow deck changing
            $questlog->setIsPublic($public ? true : false);

            if ($public) {
                $questlog->setDatePublish(new \DateTime());
            }

            foreach ($questlog->getDecks() as $deck) {
                $questlog->removeDeck($deck);
                $em->remove($deck);
            }

            $nb_decks = 0;
            $skip = 0;
            for ($i = 1; $i <= 4; $i++) {
                $deck_id = intval(filter_var($request->request->get("deck".$i."_id"), FILTER_SANITIZE_NUMBER_INT));
                $is_decklist = filter_var($request->get("deck".$i."_is_decklist"), FILTER_SANITIZE_STRING) == 'true';
                $player = trim(filter_var($request->get("deck".$i."_player_name"), FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES));

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
                        $questlog_deck->setPlayer($player);

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
                        $questlog_decklist->setDeck($decklist->getParent());
                        $questlog_decklist->setContent(json_encode($content));
                        $questlog_decklist->setDeckNumber($i - $skip);
                        $questlog_decklist->setQuestlog($questlog);
                        $questlog_decklist->setPlayer($player);

                        $questlog->addDeck($questlog_decklist);
                    }
                    $nb_decks++;
                } else {
                    $skip++;
                }
            }

            if ($nb_decks == 0) {
                throw new UnprocessableEntityHttpException("You can't save an empty quest log.");
            }

            $questlog->setNbDecks($nb_decks);
        }

        $em->persist($questlog);
        $em->flush();

        return $this->redirect($this->generateUrl('questlog_view', [
            'questlog_id' => $questlog->getId(),
            'questlog_name' => $questlog->getNameCanonical()
        ]));
    }

    public function deleteAction(Request $request) {
        /* @var $em \Doctrine\ORM\EntityManager */
        $em = $this->getDoctrine()->getManager();

        /* @var $user \AppBundle\Entity\User */
        $user = $this->getUser();
        if (!$user) {
            throw new AccessDeniedHttpException("You must be logged in for this operation.");
        }

        $questlog_id = filter_var($request->get('questlog_id'), FILTER_SANITIZE_NUMBER_INT);

        /* @var $questlog \AppBundle\Entity\Questlog */
        $questlog = $em->getRepository('AppBundle:Questlog')->find($questlog_id);
        if (!$questlog) {
            return $this->redirect($this->generateUrl('myquestlogs_list'));
        }

        if (!$questlog || $questlog->getUser()->getId() != $user->getId()) {
            throw new AccessDeniedHttpException("You don't have access to this quest log.");
        }

        if ($questlog->getNbVotes() || $questlog->getNbfavorites() || $questlog->getNbcomments()) {
            $this->get('session')->getFlashBag()->set('error', "You can't delete a published quest log.");
        } else {
            /* @var $decks \AppBundle\Entity\QuestlogDeck[] */
            $decks = $questlog->getDecks();
            foreach ($decks as $deck) {
                $em->remove($deck);
            }

            $em->remove($questlog);
            $em->flush();
        }

        return $this->redirect($this->generateUrl('myquestlogs_list'));
    }

    private function searchForm(Request $request) {
        $dbh = $this->getDoctrine()->getConnection();

        $cards_code = $request->query->get('cards');
        $author_name = filter_var($request->query->get('author'), FILTER_SANITIZE_STRING);
        $scenario = filter_var($request->query->get('scenario'), FILTER_SANITIZE_STRING);
        $nb_decks = intval(filter_var($request->query->get('nb_decks'), FILTER_SANITIZE_NUMBER_INT));

        $sort = $request->query->get('sort');
        $packs = $request->query->get('packs');

        if (!is_array($packs)) {
            $packs = $dbh->executeQuery("SELECT id FROM pack")->fetchAll(\PDO::FETCH_COLUMN);
        }

        $categories = [];
        $on = 0;
        $off = 0;
        $categories[] = ["label" => "Core / Deluxe", "packs" => []];
        $list_cycles = $this->getDoctrine()->getRepository('AppBundle:Cycle')->findBy([], ["position" => "ASC"]);
        foreach ($list_cycles as $cycle) {
            /* @var $cycle \AppBundle\Entity\Cycle */
            $size = count($cycle->getPacks());
            if ($cycle->getPosition() == 0 || $size == 0) {
                continue;
            }

            $first_pack = $cycle->getPacks()[0];
            if ($size === 1 && $first_pack->getName() == $cycle->getName()) {
                $checked = count($packs) ? in_array($first_pack->getId(), $packs) : true;
                if ($checked) {
                    $on++;
                } else {
                    $off++;
                }
                $categories[0]["packs"][] = ["id" => $first_pack->getId(), "label" => $first_pack->getName(), "checked" => $checked, "future" => $first_pack->getDateRelease() === null];
            } else {
                $category = ["label" => $cycle->getName(), "packs" => []];
                foreach ($cycle->getPacks() as $pack) {
                    $checked = count($packs) ? in_array($pack->getId(), $packs) : true;
                    if ($checked) {
                        $on++;
                    } else {
                        $off++;
                    }
                    $category['packs'][] = ["id" => $pack->getId(), "label" => $pack->getName(), "checked" => $checked, "future" => $pack->getDateRelease() === null];
                }
                $categories[] = $category;
            }
        }

        $params = [
            'allowed' => $categories,
            'on' => $on,
            'off' => $off,
            'author' => $author_name,
            'scenario' => $scenario
        ];
        $params['sort_' . $sort] = ' selected="selected"';
        $params['nb_decks_selected'] = $nb_decks;

        if (!empty($cards_code) && is_array($cards_code)) {
            $cards = $dbh->executeQuery("SELECT
    				c.name,
    				c.code,
                    s.code AS sphere_code,
                    p.name AS pack_name
    				FROM card c
                    INNER JOIN sphere s ON s.id = c.sphere_id
                    INNER JOIN pack p ON p.id = c.pack_id
                    WHERE c.code IN (?)
    				ORDER BY c.code DESC", [$cards_code], [\Doctrine\DBAL\Connection::PARAM_INT_ARRAY])->fetchAll();

            $params['cards'] = '';

            foreach ($cards as $card) {
                $params['cards'] .= $this->renderView('AppBundle:Search:card.html.twig', $card);
            }
        }

        return $this->renderView('AppBundle:QuestLog:form.html.twig', $params);
    }

    public function searchAction(Request $request) {
        $response = new Response();
        $response->setPublic();
        $response->setMaxAge($this->container->getParameter('cache_expiration'));

        $dbh = $this->getDoctrine()->getConnection();
        $spheres = $dbh->executeQuery("SELECT s.name, s.code FROM sphere s ORDER BY s.name ASC")->fetchAll();

        $owned_packs = '';
        if ($this->getUser()) {
            $owned_packs = $this->getUser()->getOwnedPacksk();
        }

        if ($owned_packs) {
            $packs = explode(",", $owned_packs);
        } else {
            $packs = $dbh->executeQuery("SELECT id FROM pack WHERE date_release IS NOT NULL")->fetchAll(\PDO::FETCH_COLUMN);
        }

        $categories = [];
        $on = 0;
        $off = 0;
        $categories[] = ["label" => "Core / Deluxe", "packs" => []];
        $list_cycles = $this->getDoctrine()->getRepository('AppBundle:Cycle')->findBy([], ["position" => "ASC"]);

        foreach ($list_cycles as $cycle) {
            /* @var $cycle \AppBundle\Entity\Cycle */
            $size = count($cycle->getPacks());
            if ($cycle->getPosition() == 0 || $size == 0) {
                continue;
            }

            $first_pack = $cycle->getPacks()[0];
            if ($size === 1 && $first_pack->getName() == $cycle->getName()) {
                $checked = count($packs) ? in_array($first_pack->getId(), $packs) : true;

                if ($checked) {
                    $on++;
                } else {
                    $off++;
                }

                $categories[0]["packs"][] = [
                    "id" => $first_pack->getId(),
                    "label" => $first_pack->getName(),
                    "checked" => $checked,
                    "future" => $first_pack->getDateRelease() === null
                ];
            } else {
                $category = ["label" => $cycle->getName(), "packs" => []];
                foreach ($cycle->getPacks() as $pack) {
                    $checked = count($packs) ? in_array($pack->getId(), $packs) : true;

                    if ($checked) {
                        $on++;
                    } else {
                        $off++;
                    }

                    $category['packs'][] = [
                        "id" => $pack->getId(),
                        "label" => $pack->getName(),
                        "checked" => $checked,
                        "future" => $pack->getDateRelease() === null
                    ];
                }
                $categories[] = $category;
            }
        }

        $searchForm = $this->renderView('AppBundle:QuestLog:form.html.twig', [
            'spheres' => $spheres,
            'allowed' => $categories,
            'on' => $on,
            'off' => $off,
            'author' => '',
            'scenario' => '',
        ]);

        return $this->render('AppBundle:QuestLog:public-questlogs.html.twig', [
            'pagetitle' => 'Quest Log Search',
            'questlogs' => null,
            'url' => $request->getRequestUri(),
            'header' => $searchForm,
            'type' => 'find',
            'pages' => null,
            'prevurl' => null,
            'nexturl' => null,
        ], $response);
    }


    public function deleteListAction(Request $request) {
        /* @var $em \Doctrine\ORM\EntityManager */
        $em = $this->getDoctrine()->getManager();

        /* @var $user \AppBundle\Entity\User */
        $user = $this->getUser();
        if (!$user) {
            throw new AccessDeniedHttpException("You must be logged in for this operation.");
        }

        $list_id = explode('-', $request->get('ids'));
        $message = null;

        foreach ($list_id as $id) {
            /* @var $questlog \AppBundle\Entity\Questlog */
            $questlog = $em->getRepository('AppBundle:Questlog')->find($id);
            if (!$questlog) {
                continue;
            }

            if ($user->getId() != $questlog->getUser()->getId()) {
                continue;
            }

            if ($questlog->getNbVotes() || $questlog->getNbfavorites() || $questlog->getNbcomments()) {
                $message = "You can't delete a published quest log. Unpublished selected quest logs were deleted.";
            } else {
                /* @var $decks \AppBundle\Entity\QuestlogDeck[] */
                $decks = $questlog->getDecks();
                foreach ($decks as $deck) {
                    $em->remove($deck);
                }

                $em->remove($questlog);
            }
        }
        $em->flush();

        $this->get('session')->getFlashBag()->set('notice', $message ?: "Quest Logs deleted.");

        return $this->redirect($this->generateUrl('myquestlogs_list'));
    }

    public function octgnexportAction($questlog_id) {
        return $this->downloadFromSelection($questlog_id, true);
    }

    public function textexportAction($questlog_id) {
        return $this->downloadFromSelection($questlog_id, false);
    }

    public function downloadFromSelection($questlog_id, $octgn) {
        /* @var $em \Doctrine\ORM\EntityManager */
        $em = $this->getDoctrine()->getManager();

        /* @var $user \AppBundle\Entity\User */
        $user = $this->getUser();
        if (!$user) {
            throw new AccessDeniedHttpException("You must be logged in for this operation.");
        }

        /* @var $questlog \AppBundle\Entity\QuestLog */
        $questlog = $em->getRepository('AppBundle:QuestLog')->find($questlog_id);
        if (!$questlog) {
            throw new AccessDeniedHttpException("You don't have access to this questlog.");
        }

        $questlog_user = $questlog->getUser();
        $is_public = $questlog->getIsPublic();

        if ($questlog_user->getId() != $user->getId() && !$questlog_user->getIsShareDecks() && !$is_public) {
            throw new AccessDeniedHttpException("You don't have access to this questlog.");
        }

        $file = tempnam("tmp", "zip");
        $zip = new \ZipArchive();
        $res = $zip->open($file, \ZipArchive::OVERWRITE);

        if ($res === true) {
            $decks = [];

            /* @var $questlog_decks \AppBundle\Entity\QuestlogDeck[] */
            $questlog_decks = $questlog->getDecks();
            foreach ($questlog_decks as $questlog_deck) {
                $deck = $questlog_deck->getDeck();
                $this->get('decks')->setSlots($deck, json_decode($questlog_deck->getContent(), true));

                $decks[] = $deck;
            }

            foreach ($decks as $deck) {
                /* @var $deck \AppBundle\Entity\Deck */
                if (!$deck) {
                    continue;
                }

                if ($octgn) {
                    $extension = 'o8d';
                    $content = $this->renderView('AppBundle:Export:octgn.xml.twig', [
                        "deck" => $deck->getTextExport()
                    ]);
                } else {
                    $extension = 'txt';
                    $content = $this->renderView('AppBundle:Export:plain.txt.twig', [
                        "deck" => $deck->getTextExport()
                    ]);
                }

                $filename = $this->get('texts')->slugify($deck->getName()) . ' ' . $deck->getVersion() . '.' . $extension;

                $zip->addFromString($filename, $content);
            }
            $zip->close();
        }
        $response = new Response();
        $response->headers->set('Content-Type', 'application/zip');
        $response->headers->set('Content-Length', filesize($file));
        $response->headers->set('Content-Disposition', $response->headers->makeDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $this->get('texts')->slugify('RingsDB - Quest Log ' . $questlog_id) . '.zip'));

        $response->setContent(file_get_contents($file));
        unlink($file);

        return $response;
    }

    public function favoriteAction(Request $request) {
        /* @var $em \Doctrine\ORM\EntityManager */
        $em = $this->getDoctrine()->getManager();

        /* @var $user \AppBundle\Entity\User */
        $user = $this->getUser();
        if (!$user) {
            throw new AccessDeniedHttpException('You must be logged in to comment.');
        }

        $questlog_id = filter_var($request->get('id'), FILTER_SANITIZE_NUMBER_INT);

        /* @var $questlog \AppBundle\Entity\QuestLog */
        $questlog = $em->getRepository('AppBundle:QuestLog')->find($questlog_id);
        if (!$questlog) {
            throw new NotFoundHttpException('Wrong id');
        }

        /* @var $author \AppBundle\Entity\User */
        $author = $questlog->getUser();

        $dbh = $this->getDoctrine()->getConnection();
        $is_favorite = $dbh->executeQuery("SELECT
				count(*)
				FROM questlog d
				JOIN questlog_favorite f ON f.questlog_id = d.id
				WHERE f.user_id = ?
				AND d.id = ?", [
            $user->getId(),
            $questlog_id
        ])->fetch(\PDO::FETCH_NUM)[0];

        if ($is_favorite) {
            $questlog->setNbfavorites($questlog->getNbFavorites() - 1);
            $questlog->removeFavorite($user);

            $questlog->setDateUpdate(new \DateTime());
            if ($author->getId() != $user->getId()) {
                $author->setReputation($author->getReputation() - 5);
            }
        } else {
            $questlog->setNbfavorites($questlog->getNbFavorites() + 1);
            $questlog->addFavorite($user);

            $questlog->setDateUpdate(new \DateTime());
            if ($author->getId() != $user->getId()) {
                $author->setReputation($author->getReputation() + 5);
            }
        }

        $em->flush();

        return new Response($questlog->getNbFavorites());
    }

    public function commentAction(Request $request) {
        /* @var $em \Doctrine\ORM\EntityManager */
        $em = $this->getDoctrine()->getManager();


        /* @var $user \AppBundle\Entity\User */
        $user = $this->getUser();
        if (!$user) {
            throw new AccessDeniedHttpException('You must be logged in to comment.');
        }

        $questlog_id = filter_var($request->get('id'), FILTER_SANITIZE_NUMBER_INT);
        $questlog = $em->getRepository('AppBundle:QuestLog')->find($questlog_id);

        $comment_text = trim($request->get('comment'));
        if ($questlog && !empty($comment_text)) {
            $comment_text = preg_replace('%(?<!\()\b(?:(?:https?|ftp)://)(?:((?:(?:[a-z\d\x{00a1}-\x{ffff}]+-?)*[a-z\d\x{00a1}-\x{ffff}]+)(?:\.(?:[a-z\d\x{00a1}-\x{ffff}]+-?)*[a-z\d\x{00a1}-\x{ffff}]+)*(?:\.[a-z\x{00a1}-\x{ffff}]{2,6}))(?::\d+)?)(?:[^\s]*)?%iu', '[$1]($0)', $comment_text);

            $mentionned_usernames = [];
            $matches = [];
            if (preg_match_all('/`@([\w_]+)`/', $comment_text, $matches, PREG_PATTERN_ORDER)) {
                $mentionned_usernames = array_unique($matches[1]);
            }

            $comment_html = $this->get('texts')->markdown($comment_text);

            $now = new DateTime();

            $comment = new QuestlogComment();
            $comment->setText($comment_html);
            $comment->setDateCreation($now);
            $comment->setUser($user);
            $comment->setQuestlog($questlog);
            $comment->setIsHidden(false);

            $em->persist($comment);

            $questlog->setDateUpdate($now);
            $questlog->setNbcomments($questlog->getNbcomments() + 1);

            $em->flush();

            // send emails
            $spool = [];
            if ($questlog->getUser()->getIsNotifAuthor()) {
                if (!isset($spool[$questlog->getUser()->getEmail()])) {
                    $spool[$questlog->getUser()->getEmail()] = 'AppBundle:Emails:newquestlogcomment_author.html.twig';
                }
            }

            foreach ($questlog->getComments() as $comment) {
                /* @var $comment \AppBundle\Entity\QuestlogComment */
                $commenter = $comment->getUser();
                if ($commenter && $commenter->getIsNotifCommenter()) {
                    if (!isset($spool[$commenter->getEmail()])) {
                        $spool[$commenter->getEmail()] = 'AppBundle:Emails:newquestlogcomment_commenter.html.twig';
                    }
                }
            }

            foreach ($mentionned_usernames as $mentionned_username) {
                /* @var $mentionned_user \AppBundle\Entity\User */
                $mentionned_user = $this->getDoctrine()->getRepository('AppBundle:User')->findOneBy(['username' => $mentionned_username]);
                if ($mentionned_user && $mentionned_user->getIsNotifMention()) {
                    if (!isset($spool[$mentionned_user->getEmail()])) {
                        $spool[$mentionned_user->getEmail()] = 'AppBundle:Emails:newquestlogcomment_mentionned.html.twig';
                    }
                }
            }
            unset($spool[$user->getEmail()]);

            $email_data = [
                'username' => $user->getUsername(),
                'questlog_name' => $questlog->getName(),
                'url' => $this->generateUrl('questlog_view', ['questlog_id' => $questlog->getId(), 'questlog_name' => $questlog->getNameCanonical()], UrlGeneratorInterface::ABSOLUTE_URL) . '#' . $comment->getId(),
                'comment' => $comment_html,
                'profile' => $this->generateUrl('user_profile_edit', [], UrlGeneratorInterface::ABSOLUTE_URL)
            ];
            foreach ($spool as $email => $view) {
                $message = \Swift_Message::newInstance()->setSubject("[ringsdb] New comment")->setFrom(["sydtrack@ringsdb.com" => $user->getUsername()])->setTo($email)->setBody($this->renderView($view, $email_data), 'text/html');
                $this->get('mailer')->send($message);
            }
        }

        return $this->redirect($this->generateUrl('questlog_view', [
            'questlog_id' => $questlog_id,
            'questlog_name' => $questlog->getNameCanonical()
        ]));
    }

    /*
     * hides a comment, or if $hidden is false, unhide a comment
     */
    public function hidecommentAction($comment_id, $hidden) {
        /* @var $user \AppBundle\Entity\User */
        $user = $this->getUser();
        if (!$user) {
            throw $this->createAccessDeniedException("You are not logged in.");
        }

        /* @var $em \Doctrine\ORM\EntityManager */
        $em = $this->getDoctrine()->getManager();

        $comment = $em->getRepository('AppBundle:QuestlogComment')->find($comment_id);
        if (!$comment) {
            throw new BadRequestHttpException('Unable to find comment');
        }

        if ($comment->getQuestlog()->getUser()->getId() !== $user->getId()) {
            return new Response(json_encode("You don't have permission to edit this comment."));
        }

        $comment->setIsHidden((boolean)$hidden);
        $em->flush();

        return new Response(json_encode(true));
    }

    /*
	 * records a user's vote
	 */
    public function voteAction(Request $request) {
        /* @var $em \Doctrine\ORM\EntityManager */
        $em = $this->getDoctrine()->getManager();

        $user = $this->getUser();
        if (!$user) {
            throw new AccessDeniedHttpException('You must be logged in to comment.');
        }

        $questlog_id = filter_var($request->get('id'), FILTER_SANITIZE_NUMBER_INT);

        /* @var $questlog \AppBundle\Entity\QuestLog */
        $questlog = $em->getRepository('AppBundle:QuestLog')->find($questlog_id);

        if ($questlog->getUser()->getId() != $user->getId()) {
            $query = $em->getRepository('AppBundle:QuestLog')
                ->createQueryBuilder('d')
                ->innerJoin('d.votes', 'u')
                ->where('d.id = :questlog_id')
                ->andWhere('u.id = :user_id')
                ->setParameter('questlog_id', $questlog_id)
                ->setParameter('user_id', $user->getId())->getQuery();

            $result = $query->getResult();
            if (empty($result)) {
                /* @var $author \AppBundle\Entity\User */
                $author = $questlog->getUser();
                $author->setReputation($author->getReputation() + 1);

                $questlog->addVote($user);
                $questlog->setDateUpdate(new \DateTime());
                $questlog->setNbVotes($questlog->getNbVotes() + 1);
                $this->getDoctrine()->getManager()->flush();
            }
        }

        return new Response($questlog->getNbVotes());
    }

    public function byauthorAction($username) {
        return $this->redirect($this->generateUrl('questlogs_list', ['type' => 'find', 'author' => $username]));
    }
}
