<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Deck;
use App\Entity\Questlog;
use App\Entity\QuestlogComment;
use App\Entity\QuestlogDeck;
use App\Model\QuestLogManager;
use App\Repository\CycleRepository;
use App\Repository\DecklistRepository;
use App\Repository\DeckRepository;
use App\Repository\QuestlogCommentRepository;
use App\Repository\QuestlogRepository;
use App\Repository\ScenarioRepository;
use App\Repository\UserRepository;
use App\Services\Decks;
use App\Services\Texts;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class QuestLogController extends AbstractController
{
    use CurrentUserTrait;
    /**
     * @var Decks
     */
    private $decks;
    /**
     * @var Texts
     */
    private $texts;
    /**
     * @var int
     */
    private $cacheExpiration;
    /**
     * @var string
     */
    private $cacheDir;
    /**
     * @var CycleRepository
     */
    private $cycleRepository;
    /**
     * @var DeckRepository
     */
    private $deckRepository;
    /**
     * @var DecklistRepository
     */
    private $decklistRepository;
    /**
     * @var QuestlogRepository
     */
    private $questlogRepository;
    /**
     * @var ScenarioRepository
     */
    private $scenarioRepository;

    public function __construct(Decks $decks, Texts $texts, int $cacheExpiration, string $cacheDir, CycleRepository $cycleRepository, DeckRepository $deckRepository, DecklistRepository $decklistRepository, QuestlogRepository $questlogRepository, ScenarioRepository $scenarioRepository)
    {
        $this->decks = $decks;
        $this->texts = $texts;
        $this->cacheExpiration = $cacheExpiration;
        $this->cacheDir = $cacheDir;
        $this->cycleRepository = $cycleRepository;
        $this->deckRepository = $deckRepository;
        $this->decklistRepository = $decklistRepository;
        $this->questlogRepository = $questlogRepository;
        $this->scenarioRepository = $scenarioRepository;
    }

    // Set the deck content to the QuestlogDeck snapshot
    public function setSnapshot($questlog): void
    {
        $questlog_decks = $questlog->getDecks();
        $decks_service = $this->decks;
        foreach ($questlog_decks as $questlog_deck) {
            $deck = $questlog_deck->getDeck();
            if (!$deck) {
                $deck = new Deck();
                $deck->setName('[deleted]');
                $questlog_deck->setDeck($deck);
            }
            $questlogdeck_content = (array) json_decode($questlog_deck->getContent());
            $decks_service->setSlots($deck, $questlogdeck_content);
        }
    }

    public function setSnapshots($questlogs): void
    {
        foreach ($questlogs as $questlog) {
            $this->setSnapshot($questlog);
        }
    }

    /**
     * @return Response
     *
     * @Route(
     *     "/myquestlogs/{scenario_name_canonical}/{quest_mode}",
     *     name="myquestlogs_list",
     *     methods={"GET"},
     *     defaults={"scenario_name_canonical"=null, "quest_mode"="normal"}
     * )
     */
    public function mylistAction($scenario_name_canonical, $quest_mode)
    {
        /* @var $em \Doctrine\ORM\EntityManager */
        $em = $this->getDoctrine()->getManager();
        // $quest_mode = 'normal';
        /* @var $quests \App\Entity\Scenario[] */
        $quests = $this->scenarioRepository->findBy([], ['position' => 'ASC']);
        /* @var $user \App\Entity\User */
        $user = $this->getUser();
        if (!$user) {
            throw new AccessDeniedHttpException('You must be logged in for this operation.');
        }
        // Count played scenarios
        $playedEasy = [];
        $playedNormal = [];
        $playedNightmare = [];
        $dbh = $em->getConnection();
        $played = $dbh->executeQuery('SELECT DISTINCT scenario_id, quest_mode, sum(success) as victory FROM questlog WHERE user_id = ? GROUP BY scenario_id, quest_mode', [$user->getId()])->fetchAll(\PDO::FETCH_NAMED);
        foreach ($played as $c) {
            if ('easy' == $c['quest_mode']) {
                $playedEasy[$c['scenario_id']] = $c['victory'];
            } elseif ('normal' == $c['quest_mode']) {
                $playedNormal[$c['scenario_id']] = $c['victory'];
            } elseif ('nightmare' == $c['quest_mode']) {
                $playedNightmare[$c['scenario_id']] = $c['victory'];
            }
        }
        if (0 == count($played)) {
            return $this->render('QuestLog/no-questlogs.html.twig', ['pagetitle' => 'My Quest Logs', 'pagedescription' => 'Log a new quest.']);
        }
        $show_all = false;
        $scenario = null;
        if (null == $scenario_name_canonical) {
            $show_all = true;
        } else {
            /* @var $scenario \App\Entity\Scenario */
            $scenario = $this->scenarioRepository->findOneBy(['nameCanonical' => $scenario_name_canonical]);
            if (null == $scenario) {
                throw new NotFoundHttpException('This quest does not exist.');
            }
        }
        if ('easy' != $quest_mode && 'nightmare' != $quest_mode) {
            $quest_mode = 'normal';
        }
        /* @var $questlogs \App\Entity\Questlog[] */
        if ($show_all) {
            $questlogs = $this->questlogRepository->findBy(['user' => $user], ['dateCreation' => 'DESC', 'id' => 'DESC']);
        } else {
            $questlogs = $this->questlogRepository->findBy(['user' => $user, 'scenario' => $scenario, 'questMode' => $quest_mode], ['dateCreation' => 'DESC', 'id' => 'DESC']);
        }
        $this->setSnapshots($questlogs);
        $victories = 0;
        $defeats = 0;
        $total = count($questlogs);
        foreach ($questlogs as $questlog) {
            // Count victories/defeats
            if ($questlog->getSuccess()) {
                ++$victories;
            } else {
                ++$defeats;
            }
        }

        return $this->render('QuestLog/my-questlogs.html.twig', ['pagetitle' => 'My Quest Logs', 'pagedescription' => 'Log a new quest.', 'quests' => $quests, 'played_easy' => $playedEasy, 'played_normal' => $playedNormal, 'played_nightmare' => $playedNightmare, 'questlogs' => $questlogs, 'quest_mode' => $quest_mode, 'selected_scenario' => $scenario, 'victories' => $victories, 'defeats' => $defeats, 'total' => $total, 'ratio' => $total ? sprintf('%.0f%%', 100 * $victories / $total) : '-', 'compact' => false]);
    }

    /**
     * @return Response
     */
    public function myCompleteListAction()
    {
        /* @var $em \Doctrine\ORM\EntityManager */
        $em = $this->getDoctrine()->getManager();
        /* @var $quests \App\Entity\Scenario[] */
        $quests = $this->scenarioRepository->findBy([], ['position' => 'ASC']);
        /* @var $user \App\Entity\User */
        $user = $this->currentUser();
        // Count played scenarios
        $playedEasy = [];
        $playedNormal = [];
        $playedNightmare = [];
        $dbh = $em->getConnection();
        $played = $dbh->executeQuery('SELECT DISTINCT scenario_id, quest_mode, sum(success) as victory FROM questlog WHERE user_id = ? GROUP BY scenario_id, quest_mode', [$user->getId()])->fetchAll(\PDO::FETCH_NAMED);
        foreach ($played as $c) {
            if ('easy' == $c['quest_mode']) {
                $playedEasy[$c['scenario_id']] = $c['victory'];
            } elseif ('normal' == $c['quest_mode']) {
                $playedNormal[$c['scenario_id']] = $c['victory'];
            } elseif ('nightmare' == $c['quest_mode']) {
                $playedNightmare[$c['scenario_id']] = $c['victory'];
            }
        }
        /* @var $questlogs \App\Entity\Questlog[] */
        $questlogs = $this->questlogRepository->findBy(['user' => $user], ['dateCreation' => 'DESC', 'id' => 'DESC']);
        $this->setSnapshots($questlogs);

        return $this->render('QuestLog/my-questlogs.html.twig', ['pagetitle' => 'My Quest Logs', 'pagedescription' => 'Log a new quest.', 'quests' => $quests, 'played_easy' => $playedEasy, 'played_normal' => $playedNormal, 'played_nightmare' => $playedNightmare, 'questlogs' => $questlogs, 'quest_mode' => 'normal', 'compact' => true]);
    }

    /**
     * @return Response
     *
     * @Route(
     *     "/questlog/new/{public}/{deck1_id}/{deck2_id}/{deck3_id}/{deck4_id}",
     *     name="questlog_new",
     *     methods={"GET"},
     *     requirements={"public"="\d+", "deck1_id"="\d+", "deck2_id"="\d+", "deck3_id"="\d+", "deck4_id"="\d+"},
     *     defaults={"public"=0, "deck1_id"=null, "deck2_id"=null, "deck3_id"=null, "deck4_id"=null}
     * )
     */
    public function newAction($deck1_id, $deck2_id, $deck3_id, $deck4_id, $public)
    {
        /* @var $em \Doctrine\ORM\EntityManager */
        $em = $this->getDoctrine()->getManager();
        $response = new Response();
        /* @var $quests \App\Entity\Scenario[] */
        $quests = $this->scenarioRepository->findBy([], ['position' => 'ASC']);
        /* @var $decks \App\Entity\Deck[] */
        $decks = [];
        $deck_ids = func_get_args();
        $author_names = [];
        for ($i = 0; $i < 4; ++$i) {
            $decks[$i] = null;
            $author_names[$i] = null;
            if ($deck_ids[$i]) {
                /* $public = filter_var($request->get('p'.($i + 1)), FILTER_SANITIZE_NUMBER_INT); */
                if ($public) {
                    $decks[$i] = $this->decklistRepository->find($deck_ids[$i]);
                } else {
                    $decks[$i] = $this->deckRepository->find($deck_ids[$i]);
                }
                if ($decks[$i]) {
                    $user = $decks[$i]->getUser();
                    $author_names[$i] = $user->getUsername();
                    if (!$public && !$user->getIsShareDecks() && $user->getId() != $this->currentUser()->getId()) {
                        $decks[$i] = null;
                    }
                }
            }
        }
        $questlog = new Questlog();
        $questlog->setSuccess(true);

        return $this->render('QuestLog/edit.html.twig', ['quests' => $quests, 'pagetitle' => 'Log a Quest', 'deck1' => $decks[0], 'deck2' => $decks[1], 'deck3' => $decks[2], 'deck4' => $decks[3], 'questlogdeck1_content' => null, 'questlogdeck2_content' => null, 'questlogdeck3_content' => null, 'questlogdeck4_content' => null, 'questlogdeck1_player_name' => $author_names[0], 'questlogdeck2_player_name' => $author_names[1], 'questlogdeck3_player_name' => $author_names[2], 'questlogdeck4_player_name' => $author_names[3], 'questlog' => $questlog, 'is_locked_as_public' => false, 'nbDecks' => 0], $response);
    }

    /**
     * @return Response
     *
     * @Route(
     *     "/questlog/edit/{questlog_id}",
     *     name="questlog_edit",
     *     methods={"GET"},
     *     requirements={"questlog_id"="\d+"}
     * )
     */
    public function editAction($questlog_id)
    {
        /* @var $em \Doctrine\ORM\EntityManager */
        $em = $this->getDoctrine()->getManager();
        $response = new Response();
        /* @var $user \App\Entity\User */
        $user = $this->currentUser();
        /* @var $questlog \App\Entity\Questlog */
        $questlog = $this->questlogRepository->find($questlog_id);
        if (!$questlog) {
            throw new NotFoundHttpException('This questlog does not exists.');
        }
        if ($user->getId() !== $questlog->getUser()->getId()) {
            throw new AccessDeniedHttpException('Access denied to this object.');
        }
        /* @var $quests \App\Entity\Scenario[] */
        $quests = $this->scenarioRepository->findBy([], ['position' => 'ASC']);
        $is_locked_as_public = $questlog->getNbVotes() > 0 || $questlog->getNbFavorites() > 0 || $questlog->getNbComments() > 0;
        $data = ['quests' => $quests, 'pagetitle' => 'Edit Quest Log', 'deck1' => null, 'deck2' => null, 'deck3' => null, 'deck4' => null, 'questlogdeck1_content' => null, 'questlogdeck2_content' => null, 'questlogdeck3_content' => null, 'questlogdeck4_content' => null, 'questlogdeck1_player_name' => null, 'questlogdeck2_player_name' => null, 'questlogdeck3_player_name' => null, 'questlogdeck4_player_name' => null, 'questlog' => $questlog, 'is_locked_as_public' => $is_locked_as_public, 'nbDecks' => $questlog->getNbDecks()];
        /* @var $questlog_decks \App\Entity\QuestlogDeck[] */
        $questlog_decks = $questlog->getDecks();
        foreach ($questlog_decks as $questlog_deck) {
            $data['deck'.$questlog_deck->getDeckNumber()] = $questlog_deck->getDecklist() ?: $questlog_deck->getDeck();
            $data['questlogdeck'.$questlog_deck->getDeckNumber().'_content'] = $questlog_deck->getContent();
            $data['questlogdeck'.$questlog_deck->getDeckNumber().'_player_name'] = $questlog_deck->getPlayer();
        }

        return $this->render('QuestLog/edit.html.twig', $data, $response);
    }

    /**
     * @return Response
     *
     * @Route(
     *     "/questlog/view/{questlog_id}/{questlog_name}",
     *     name="questlog_view",
     *     methods={"GET"},
     *     requirements={"questlog_id"="\d+"},
     *     defaults={"questlog_name"=null}
     * )
     */
    public function viewAction($questlog_id)
    {
        /* @var $questlog \App\Entity\Questlog */
        $questlog = $this->questlogRepository->find($questlog_id);
        // $this->setSnapshot($questlog);
        if (!$questlog) {
            throw new NotFoundHttpException('This questlog does not exists.');
        }
        $is_owner = $this->getUser() && $this->getUser()->getId() == $questlog->getUser()->getId();
        $is_public = $questlog->getIsPublic();
        if (!$questlog->getUser()->getIsShareDecks() && !$is_owner && !$is_public) {
            throw new AccessDeniedHttpException('You are not allowed to view this questlog. To get access, you can ask it\'s owner to enable "Share my decks" on their account.');
        }
        if ($is_public) {
            $commenters = array_map(function ($comment) {
                /* @var $comment \App\Entity\QuestlogComment */
                return $comment->getUser()->getUsername();
            }, $questlog->getComments()->getValues());
            $commenters[] = $questlog->getUser()->getUsername();
        } else {
            $commenters = [];
        }
        $data = ['pagetitle' => $questlog->getScenario()->getName().' - Quest Log', 'deck1' => null, 'deck2' => null, 'deck3' => null, 'deck4' => null, 'questlogdeck1_content' => null, 'questlogdeck2_content' => null, 'questlogdeck3_content' => null, 'questlogdeck4_content' => null, 'questlogdeck1_player_name' => null, 'questlogdeck2_player_name' => null, 'questlogdeck3_player_name' => null, 'questlogdeck4_player_name' => null, 'questlog' => $questlog, 'is_owner' => $is_owner, 'is_public' => $is_public, 'commenters' => $commenters, 'nbDecks' => $questlog->getNbDecks()];
        /* @var $questlog_decks \App\Entity\QuestlogDeck[] */
        $questlog_decks = $questlog->getDecks();
        foreach ($questlog_decks as $questlog_deck) {
            $data['deck'.$questlog_deck->getDeckNumber()] = $questlog_deck->getDecklist() ?: $questlog_deck->getDeck();
            $data['questlogdeck'.$questlog_deck->getDeckNumber()] = $questlog_deck;
            $data['questlogdeck'.$questlog_deck->getDeckNumber().'_content'] = $questlog_deck->getContent();
            $data['questlogdeck'.$questlog_deck->getDeckNumber().'_player_name'] = $questlog_deck->getPlayer();
        }

        return $this->render('QuestLog/view.html.twig', $data);
    }

    /**
     * @return Response
     *
     * @Route("/questlog/save", name="questlog_save", methods={"POST"})
     */
    public function saveAction(Request $request)
    {
        /* @var $em \Doctrine\ORM\EntityManager */
        $em = $this->getDoctrine()->getManager();
        /* @var $user \App\Entity\User */
        $user = $this->currentUser();
        $questlog_id = intval(filter_var($request->request->get('questlog_id'), FILTER_SANITIZE_NUMBER_INT));
        if ($questlog_id) {
            /* @var $questlog \App\Entity\Questlog */
            $questlog = $this->questlogRepository->find($questlog_id);
            if (!$questlog) {
                throw new NotFoundHttpException('This questlog does not exist.');
            }
            if ($user->getId() !== $questlog->getUser()->getId()) {
                throw new AccessDeniedHttpException('Access denied to this object.');
            }
        } else {
            $questlog = new Questlog();
            $questlog->setNbVotes(0);
            $questlog->setNbComments(0);
            $questlog->setNbFavorites(0);
            $questlog->setNbDecks(0);
        }
        $name = trim((string) filter_var($request->request->get('name'), FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES));
        $name = substr($name, 0, 250);
        if (empty($name)) {
            $name = 'Untitled Questlog';
        }
        $descriptionMd = trim($request->request->get('descriptionMd'));
        $descriptionHtml = $this->texts->markdown($descriptionMd);
        $quest = intval(filter_var($request->request->get('quest'), FILTER_SANITIZE_NUMBER_INT));
        $date = trim((string) filter_var($request->request->get('date'), FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES));
        $difficulty = trim((string) filter_var($request->request->get('difficulty'), FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES));
        $victory = trim((string) filter_var($request->request->get('victory'), FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES));
        $score = intval(filter_var($request->request->get('score'), FILTER_SANITIZE_NUMBER_INT));
        $public = boolval(filter_var($request->request->get('public'), FILTER_SANITIZE_NUMBER_INT));
        $victory = 'no' == $victory ? false : true;
        $difficulty = in_array($difficulty, ['normal', 'easy', 'nightmare']) ? $difficulty : 'normal';
        /* @var $scenario \App\Entity\Scenario */
        $scenario = $this->scenarioRepository->find($quest);
        if (!$scenario) {
            throw new NotFoundHttpException('This scenario does not exists.');
        }
        $date = new \DateTime($date);
        $questlog->setUser($user);
        $questlog->setName($name);
        $questlog->setNameCanonical($this->texts->slugify($name));
        $questlog->setDescriptionMd($descriptionMd);
        $questlog->setDescriptionHtml($descriptionHtml);
        $questlog->setScenario($scenario);
        $questlog->setDatePlayed($date);
        $questlog->setQuestMode($difficulty);
        $questlog->setSuccess($victory);
        $questlog->setScore($score);
        $is_locked_as_public = $questlog->getNbVotes() > 0 || $questlog->getNbFavorites() > 0 || $questlog->getNbComments() > 0;
        if (!$is_locked_as_public) {
            // Allow deck changing
            $questlog->setIsPublic($public ? true : false);
            if ($public) {
                $questlog->setDatePublish(new \DateTime());
            }
            $questlogdecks = $questlog->getDecks();
            foreach ($questlog->getDecks() as $deck) {
                $questlog->removeDeck($deck);
                $em->remove($deck);
            }
            $nb_decks = 0;
            $skip = 0;
            for ($i = 1; $i <= 4; ++$i) {
                $deck_id = intval(filter_var($request->request->get('deck'.$i.'_id'), FILTER_SANITIZE_NUMBER_INT));
                $is_decklist = 'true' == filter_var($request->get('deck'.$i.'_is_decklist'), FILTER_SANITIZE_STRING);
                $player = trim((string) filter_var($request->get('questlogdeck'.$i.'_player_name'), FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES));
                $content = (array) json_decode($request->get('questlogdeck'.$i.'_content'));
                if ($deck_id) {
                    if (!$is_decklist) {
                        /* @var $deck \App\Entity\Deck */
                        $deck = $this->deckRepository->find($deck_id);
                        if (!$deck) {
                            throw new NotFoundHttpException('One of the selected decks does not exist.');
                        }
                        $deck_user = $deck->getUser();
                        $is_owner = $user->getId() == $deck_user->getId();
                        if (!$is_owner && !$deck_user->getIsShareDecks()) {
                            throw new AccessDeniedHttpException('You are not allowed to view this deck. To get access, you can ask the deck owner to enable "Share my decks" on their account.');
                        }
                        if (!$is_owner) {
                            $deck = $this->decks->cloneDeck($deck, $user);
                        }
                        // $content = (array) json_decode($request->get("deck".$i."_content"));
                        if (!isset($content['main']) || empty($content['main'])) {
                            return new Response('Cannot save a questlog with an empty deck');
                        }
                        $questlog_deck = new QuestlogDeck();
                        $questlog_deck->setDeck($deck);
                        $questlog_deck->setContent((string) json_encode($content));
                        $questlog_deck->setDeckNumber($i - $skip);
                        $questlog_deck->setQuestlog($questlog);
                        $questlog_deck->setPlayer($player);
                        $questlog->addDeck($questlog_deck);
                    } else {
                        /* @var $decklist \App\Entity\Decklist */
                        $decklist = $this->decklistRepository->find($deck_id);
                        if (!$decklist) {
                            throw new NotFoundHttpException('One of the selected decks does not exist.');
                        }
                        // $content = (array) json_decode($request->get("deck".$i."_content"));
                        if (!isset($content['main']) || empty($content['main'])) {
                            return new Response('Cannot save a questlog with an empty deck');
                        }
                        $questlog_decklist = new QuestlogDeck();
                        $questlog_decklist->setDecklist($decklist);
                        $questlog_decklist->setDeck($decklist->getParent());
                        $questlog_decklist->setContent((string) json_encode($content));
                        $questlog_decklist->setDeckNumber($i - $skip);
                        $questlog_decklist->setQuestlog($questlog);
                        $questlog_decklist->setPlayer($player);
                        $questlog->addDeck($questlog_decklist);
                    }
                    ++$nb_decks;
                } else {
                    // deck_id == 0 occurs if:
                    // 1. the deck slot in the builder is empty
                    // 2. the deck being referenced was deleted
                    $content = json_decode($request->get('deck'.$i.'_content') ?? '', true);
                    if (!isset($content['main']) || empty($content['main'])) {
                        // Deck slot was empty
                        ++$skip;
                        continue;
                    }
                    // Reference deck was deleted
                    $questlog_deck = new QuestlogDeck();
                    $questlog_deck->setContent((string) json_encode($content));
                    $questlog_deck->setDeckNumber($i - $skip);
                    $questlog_deck->setQuestlog($questlog);
                    $questlog_deck->setPlayer($player);
                    $questlog->addDeck($questlog_deck);
                    ++$nb_decks;
                }
            }
            if (0 == $nb_decks) {
                throw new UnprocessableEntityHttpException("You can't save an empty quest log.");
            }
            $questlog->setNbDecks($nb_decks);
        }
        $em->persist($questlog);
        $em->flush();

        return $this->redirect($this->generateUrl('questlog_view', ['questlog_id' => $questlog->getId(), 'questlog_name' => $questlog->getNameCanonical()]));
    }

    /**
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     *
     * @Route("/questlog/delete", name="questlog_delete", methods={"POST"})
     */
    public function deleteAction(Request $request)
    {
        /* @var $em \Doctrine\ORM\EntityManager */
        $em = $this->getDoctrine()->getManager();
        /* @var $user \App\Entity\User */
        $user = $this->getUser();
        if (!$user) {
            throw new AccessDeniedHttpException('You must be logged in for this operation.');
        }
        $questlog_id = filter_var($request->get('questlog_id'), FILTER_SANITIZE_NUMBER_INT);
        /* @var $questlog \App\Entity\Questlog */
        $questlog = $this->questlogRepository->find($questlog_id);
        if (!$questlog) {
            return $this->redirect($this->generateUrl('myquestlogs_list'));
        }
        if ($questlog->getUser()->getId() != $user->getId()) {
            throw new AccessDeniedHttpException("You don't have access to this quest log.");
        }
        if ($questlog->getNbVotes() || $questlog->getNbfavorites() || $questlog->getNbcomments()) {
            $this->get('session')->getFlashBag()->set('error', "You can't delete a published quest log.");
        } else {
            /* @var $decks \App\Entity\QuestlogDeck[] */
            $decks = $questlog->getDecks();
            foreach ($decks as $deck) {
                $em->remove($deck);
            }
            $em->remove($questlog);
            $em->flush();
        }

        return $this->redirect($this->generateUrl('myquestlogs_list'));
    }

    /**
     * @return string
     */
    private function searchForm(Request $request)
    {
        $dbh = $this->getDoctrine()->getConnection();
        $cards_code = $request->query->get('cards');
        $author_name = filter_var($request->query->get('author'), FILTER_SANITIZE_STRING);
        $questlog_name = filter_var($request->query->get('name'), FILTER_SANITIZE_STRING);
        $scenario = filter_var($request->query->get('scenario'), FILTER_SANITIZE_STRING);
        $nb_decks = intval(filter_var($request->query->get('nb_decks'), FILTER_SANITIZE_NUMBER_INT));
        $sort = $request->query->get('sort');
        $packs = $request->query->get('packs');
        if (!is_array($packs)) {
            $packs = $dbh->executeQuery('SELECT id FROM pack')->fetchAll(\PDO::FETCH_COLUMN);
        }
        $categories = [];
        $on = 0;
        $off = 0;
        $categories[] = ['label' => 'Core / Deluxe', 'packs' => []];
        $list_cycles = $this->cycleRepository->findBy([], ['position' => 'ASC']);
        foreach ($list_cycles as $cycle) {
            /* @var $cycle \App\Entity\Cycle */
            $size = count($cycle->getPacks());
            $first_pack = $cycle->getPacks()->first();
            if (0 == $cycle->getPosition() || false === $first_pack) {
                continue;
            }
            if (1 === $size && $first_pack->getName() == $cycle->getName()) {
                $checked = count($packs) ? in_array($first_pack->getId(), $packs) : true;
                if ($checked) {
                    ++$on;
                } else {
                    ++$off;
                }
                $categories[0]['packs'][] = ['id' => $first_pack->getId(), 'label' => $first_pack->getName(), 'checked' => $checked, 'future' => null === $first_pack->getDateRelease()];
            } else {
                $category = ['label' => $cycle->getName(), 'packs' => []];
                foreach ($cycle->getPacks() as $pack) {
                    $checked = count($packs) ? in_array($pack->getId(), $packs) : true;
                    if ($checked) {
                        ++$on;
                    } else {
                        ++$off;
                    }
                    $category['packs'][] = ['id' => $pack->getId(), 'label' => $pack->getName(), 'checked' => $checked, 'future' => null === $pack->getDateRelease()];
                }
                $categories[] = $category;
            }
        }
        $params = ['name' => $questlog_name, 'allowed' => $categories, 'on' => $on, 'off' => $off, 'author' => $author_name, 'scenario' => $scenario];
        $params['sort_'.$sort] = ' selected="selected"';
        $params['nb_decks_selected'] = $nb_decks;
        if (!empty($cards_code) && is_array($cards_code)) {
            $cards = $dbh->executeQuery("SELECT\n    \t\t\t\tc.name,\n    \t\t\t\tc.code,\n                    s.code AS sphere_code,\n                    t.name AS type_name\n    \t\t\t\tFROM card c\n                    INNER JOIN sphere s ON s.id = c.sphere_id\n                    INNER JOIN type t ON t.id = c.type_id\n                    INNER JOIN card_printing cpr ON cpr.id = (SELECT cp2.id FROM card_printing cp2 WHERE cp2.card_id = c.id ORDER BY cp2.position ASC, cp2.id ASC LIMIT 1)\n                    INNER JOIN pack p ON p.id = cpr.pack_id\n                    WHERE c.code IN (?)\n    \t\t\t\tORDER BY c.code DESC", [$cards_code], [\Doctrine\DBAL\Connection::PARAM_INT_ARRAY])->fetchAll();
            $params['cards'] = '';
            foreach ($cards as $card) {
                $params['cards'] .= $this->renderView('Search/card.html.twig', $card);
            }
        }

        return $this->renderView('QuestLog/form.html.twig', $params);
    }

    /**
     * @return Response
     *
     * @Route("/questlogs/search", name="questlogs_searchform", methods={"GET"})
     */
    public function searchAction(Request $request)
    {
        $response = new Response();
        $response->setPublic();
        $response->setMaxAge($this->cacheExpiration);
        $dbh = $this->getDoctrine()->getConnection();
        $spheres = $dbh->executeQuery('SELECT s.name, s.code FROM sphere s ORDER BY s.name ASC')->fetchAll();
        $owned_packs = '';
        if ($this->getUser()) {
            $owned_packs = $this->getUser()->getOwnedPacks();
        }
        if ($owned_packs) {
            // owned_packs is a per-pack count map ("id" / "id:count", legacy "id-2");
            // keep the ids whose count is > 0.
            $packs = [];
            foreach (explode(',', $owned_packs) as $token) {
                if (preg_match('/^(\\d+)(?:[:-](\\d+))?$/', trim($token), $m)) {
                    if (!isset($m[2]) || (int) $m[2] > 0) {
                        $packs[] = (int) $m[1];
                    }
                }
            }
        } else {
            $packs = $dbh->executeQuery('SELECT id FROM pack WHERE date_release IS NOT NULL')->fetchAll(\PDO::FETCH_COLUMN);
        }
        $categories = [];
        $on = 0;
        $off = 0;
        $categories[] = ['label' => 'Core / Deluxe', 'packs' => []];
        $list_cycles = $this->cycleRepository->findBy([], ['position' => 'ASC']);
        foreach ($list_cycles as $cycle) {
            /* @var $cycle \App\Entity\Cycle */
            $size = count($cycle->getPacks());
            $first_pack = $cycle->getPacks()->first();
            if (0 == $cycle->getPosition() || false === $first_pack) {
                continue;
            }
            if (1 === $size && $first_pack->getName() == $cycle->getName()) {
                $checked = count($packs) ? in_array($first_pack->getId(), $packs) : true;
                if ($checked) {
                    ++$on;
                } else {
                    ++$off;
                }
                $categories[0]['packs'][] = ['id' => $first_pack->getId(), 'label' => $first_pack->getName(), 'checked' => $checked, 'future' => null === $first_pack->getDateRelease()];
            } else {
                $category = ['label' => $cycle->getName(), 'packs' => []];
                foreach ($cycle->getPacks() as $pack) {
                    $checked = count($packs) ? in_array($pack->getId(), $packs) : true;
                    if ($checked) {
                        ++$on;
                    } else {
                        ++$off;
                    }
                    $category['packs'][] = ['id' => $pack->getId(), 'label' => $pack->getName(), 'checked' => $checked, 'future' => null === $pack->getDateRelease()];
                }
                $categories[] = $category;
            }
        }
        $searchForm = $this->renderView('QuestLog/form.html.twig', ['name' => '', 'spheres' => $spheres, 'allowed' => $categories, 'on' => $on, 'off' => $off, 'author' => '', 'scenario' => '']);

        return $this->render('QuestLog/public-questlogs.html.twig', ['pagetitle' => 'Quest Log Search', 'questlogs' => null, 'url' => $request->getRequestUri(), 'header' => $searchForm, 'type' => 'find', 'pages' => null, 'prevurl' => null, 'nexturl' => null], $response);
    }

    /**
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     *
     * @Route("/questlog/delete_list", name="questlog_delete_list", methods={"POST"})
     */
    public function deleteListAction(Request $request)
    {
        /* @var $em \Doctrine\ORM\EntityManager */
        $em = $this->getDoctrine()->getManager();
        /* @var $user \App\Entity\User */
        $user = $this->getUser();
        if (!$user) {
            throw new AccessDeniedHttpException('You must be logged in for this operation.');
        }
        $list_id = explode('-', $request->get('ids'));
        $message = null;
        foreach ($list_id as $id) {
            /* @var $questlog \App\Entity\Questlog */
            $questlog = $this->questlogRepository->find($id);
            if (!$questlog) {
                continue;
            }
            if ($user->getId() != $questlog->getUser()->getId()) {
                continue;
            }
            if ($questlog->getNbVotes() || $questlog->getNbfavorites() || $questlog->getNbcomments()) {
                $message = "You can't delete a published quest log. Unpublished selected quest logs were deleted.";
            } else {
                /* @var $decks \App\Entity\QuestlogDeck[] */
                $decks = $questlog->getDecks();
                foreach ($decks as $deck) {
                    $em->remove($deck);
                }
                $em->remove($questlog);
            }
        }
        $em->flush();
        $this->get('session')->getFlashBag()->set('notice', $message ?: 'Quest Logs deleted.');

        return $this->redirect($this->generateUrl('myquestlogs_list'));
    }

    /**
     * @return Response
     *
     * @Route(
     *     "/questlog/export/octgn/{questlog_id}",
     *     name="questlog_export_octgn",
     *     methods={"GET"},
     *     requirements={"questlog_id"="\d+"}
     * )
     */
    public function octgnexportAction($questlog_id)
    {
        return $this->downloadFromSelection($questlog_id, true);
    }

    /**
     * @return Response
     *
     * @Route(
     *     "/questlog/export/text/{questlog_id}",
     *     name="questlog_export_text",
     *     methods={"GET"},
     *     requirements={"questlog_id"="\d+"}
     * )
     */
    public function textexportAction($questlog_id)
    {
        return $this->downloadFromSelection($questlog_id, false);
    }

    /**
     * @return Response
     */
    public function downloadFromSelection($questlog_id, $octgn)
    {
        /* @var $em \Doctrine\ORM\EntityManager */
        $em = $this->getDoctrine()->getManager();
        /* @var $user \App\Entity\User */
        $user = $this->getUser();
        if (!$user) {
            throw new AccessDeniedHttpException('You must be logged in for this operation.');
        }
        /* @var $questlog \App\Entity\QuestLog */
        $questlog = $this->questlogRepository->find($questlog_id);
        if (!$questlog) {
            throw new AccessDeniedHttpException("You don't have access to this questlog.");
        }
        $questlog_user = $questlog->getUser();
        $is_public = $questlog->getIsPublic();
        if ($questlog_user->getId() != $user->getId() && !$questlog_user->getIsShareDecks() && !$is_public) {
            throw new AccessDeniedHttpException("You don't have access to this questlog.");
        }
        $tmpDir = $this->cacheDir;
        $file = tempnam($tmpDir, 'zip');
        if (false === $file) {
            throw new \RuntimeException("Cannot create a temporary file in {$tmpDir}");
        }
        $zip = new \ZipArchive();
        $res = $zip->open($file, \ZipArchive::OVERWRITE);
        if (true === $res) {
            $decks = [];
            /* @var $questlog_decks \App\Entity\QuestlogDeck[] */
            $questlog_decks = $questlog->getDecks();
            foreach ($questlog_decks as $questlog_deck) {
                $deck = $questlog_deck->getDeck();
                $this->decks->setSlots($deck, json_decode($questlog_deck->getContent(), true));
                $decks[] = $deck;
            }
            foreach ($decks as $deck) {
                /* @var $deck \App\Entity\Deck */
                if (!$deck) {
                    continue;
                }
                if ($octgn) {
                    $extension = 'o8d';
                    $content = $this->renderView('Export/octgn.xml.twig', ['deck' => $deck->getTextExport()]);
                } else {
                    $extension = 'txt';
                    $content = $this->renderView('Export/plain.txt.twig', ['deck' => $deck->getTextExport()]);
                }
                $filename = $this->texts->slugify($deck->getName()).' '.$deck->getVersion().'.'.$extension;
                $zip->addFromString($filename, $content);
            }
            $zip->close();
        }
        $response = new Response();
        $response->headers->set('Content-Type', 'application/zip');
        $response->headers->set('Content-Length', (string) filesize($file));
        $response->headers->set('Content-Disposition', $response->headers->makeDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $this->texts->slugify('RingsDB - Quest Log '.$questlog_id).'.zip'));
        $response->setContent(file_get_contents($file));
        unlink($file);

        return $response;
    }

    /**
     * @return Response
     *
     * @Route("/user/questlog_favorite", name="questlog_favorite", methods={"POST"})
     */
    public function favoriteAction(Request $request)
    {
        /* @var $em \Doctrine\ORM\EntityManager */
        $em = $this->getDoctrine()->getManager();
        /* @var $user \App\Entity\User */
        $user = $this->getUser();
        if (!$user) {
            throw new AccessDeniedHttpException('You must be logged in to comment.');
        }
        $questlog_id = filter_var($request->get('id'), FILTER_SANITIZE_NUMBER_INT);
        /* @var $questlog \App\Entity\QuestLog */
        $questlog = $this->questlogRepository->find($questlog_id);
        if (!$questlog) {
            throw new NotFoundHttpException('Wrong id');
        }
        /* @var $author \App\Entity\User */
        $author = $questlog->getUser();
        $dbh = $this->getDoctrine()->getConnection();
        $is_favorite = $dbh->executeQuery("SELECT\n\t\t\t\tcount(*)\n\t\t\t\tFROM questlog d\n\t\t\t\tJOIN questlog_favorite f ON f.questlog_id = d.id\n\t\t\t\tWHERE f.user_id = ?\n\t\t\t\tAND d.id = ?", [$user->getId(), $questlog_id])->fetch(\PDO::FETCH_NUM)[0];
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

    /**
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     *
     * @Route("/user/questlog_comment", name="questlog_comment", methods={"POST"})
     */
    public function commentAction(Request $request, MailerInterface $mailer, UserRepository $userRepository)
    {
        /* @var $em \Doctrine\ORM\EntityManager */
        $em = $this->getDoctrine()->getManager();
        /* @var $user \App\Entity\User */
        $user = $this->getUser();
        if (!$user) {
            throw new AccessDeniedHttpException('You must be logged in to comment.');
        }
        $questlog_id = filter_var($request->get('id'), FILTER_SANITIZE_NUMBER_INT);
        $questlog = $this->questlogRepository->find($questlog_id);
        if (!$questlog) {
            throw new BadRequestHttpException('Wrong quest log id');
        }
        $comment_text = trim($request->get('comment'));
        if (!empty($comment_text)) {
            $comment_text = (string) preg_replace('%(?<!\\()\\b(?:(?:https?|ftp)://)(?:((?:(?:[a-z\\d\\x{00a1}-\\x{ffff}]+-?)*[a-z\\d\\x{00a1}-\\x{ffff}]+)(?:\\.(?:[a-z\\d\\x{00a1}-\\x{ffff}]+-?)*[a-z\\d\\x{00a1}-\\x{ffff}]+)*(?:\\.[a-z\\x{00a1}-\\x{ffff}]{2,6}))(?::\\d+)?)(?:[^\\s]*)?%iu', '[$1]($0)', $comment_text);
            $mentionned_usernames = [];
            $matches = [];
            if (preg_match_all('/`@([\\w_]+)`/', $comment_text, $matches, PREG_PATTERN_ORDER)) {
                $mentionned_usernames = array_unique($matches[1]);
            }
            $comment_html = $this->texts->markdown($comment_text);
            $now = new \DateTime();
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
                $spool[$questlog->getUser()->getEmail()] = 'Emails/newquestlogcomment_author.html.twig';
            }
            foreach ($questlog->getComments() as $comment) {
                /* @var $comment \App\Entity\QuestlogComment */
                $commenter = $comment->getUser();
                if ($commenter->getIsNotifCommenter()) {
                    if (!isset($spool[$commenter->getEmail()])) {
                        $spool[$commenter->getEmail()] = 'Emails/newquestlogcomment_commenter.html.twig';
                    }
                }
            }
            foreach ($mentionned_usernames as $mentionned_username) {
                /* @var $mentionned_user \App\Entity\User */
                $mentionned_user = $userRepository->findOneBy(['username' => $mentionned_username]);
                if ($mentionned_user && $mentionned_user->getIsNotifMention()) {
                    if (!isset($spool[$mentionned_user->getEmail()])) {
                        $spool[$mentionned_user->getEmail()] = 'Emails/newquestlogcomment_mentionned.html.twig';
                    }
                }
            }
            unset($spool[$user->getEmail()]);
            $email_data = ['username' => $user->getUsername(), 'questlog_name' => $questlog->getName(), 'url' => $this->generateUrl('questlog_view', ['questlog_id' => $questlog->getId(), 'questlog_name' => $questlog->getNameCanonical()], UrlGeneratorInterface::ABSOLUTE_URL).'#'.$comment->getId(), 'comment' => $comment_html, 'profile' => $this->generateUrl('user_profile_edit', [], UrlGeneratorInterface::ABSOLUTE_URL)];
            foreach ($spool as $email => $view) {
                $message = (new Email())->subject('[ringsdb] New comment')->from(new Address('seastan@ringsdb.com', 'Seastan'))->to(new Address($email, $user->getUsername()))->html($this->renderView($view, $email_data));
                $mailer->send($message);
            }
        }

        return $this->redirect($this->generateUrl('questlog_view', ['questlog_id' => $questlog_id, 'questlog_name' => $questlog->getNameCanonical()]));
    }

    /*
     * hides a comment, or if $hidden is false, unhide a comment
     */
    /**
     * @return Response
     *
     * @Route(
     *     "/user/questlog_hidecomment/{comment_id}/{hidden}",
     *     name="questlog_comment_hide",
     *     methods={"POST"}
     * )
     */
    public function hidecommentAction($comment_id, $hidden, QuestlogCommentRepository $questlogCommentRepository)
    {
        /* @var $user \App\Entity\User */
        $user = $this->getUser();
        if (!$user) {
            throw $this->createAccessDeniedException('You are not logged in.');
        }
        /* @var $em \Doctrine\ORM\EntityManager */
        $em = $this->getDoctrine()->getManager();
        $comment = $questlogCommentRepository->find($comment_id);
        if (!$comment) {
            throw new BadRequestHttpException('Unable to find comment');
        }
        if ($comment->getQuestlog()->getUser()->getId() !== $user->getId()) {
            return new Response(json_encode("You don't have permission to edit this comment."));
        }
        $comment->setIsHidden((bool) $hidden);
        $em->flush();

        return new Response(json_encode(true));
    }

    /*
     * records a user's vote
     */
    /**
     * @return Response
     *
     * @Route("/user/questlog_like", name="questlog_like", methods={"POST"})
     */
    public function voteAction(Request $request)
    {
        /* @var $em \Doctrine\ORM\EntityManager */
        $em = $this->getDoctrine()->getManager();
        $user = $this->getUser();
        if (!$user) {
            throw new AccessDeniedHttpException('You must be logged in to comment.');
        }
        $questlog_id = filter_var($request->get('id'), FILTER_SANITIZE_NUMBER_INT);
        /* @var $questlog \App\Entity\QuestLog */
        $questlog = $this->questlogRepository->find($questlog_id);
        if (!$questlog) {
            throw new BadRequestHttpException('Unable to find quest log');
        }
        if ($questlog->getUser()->getId() != $user->getId()) {
            $query = $this->questlogRepository->createQueryBuilder('d')->innerJoin('d.votes', 'u')->where('d.id = :questlog_id')->andWhere('u.id = :user_id')->setParameter('questlog_id', $questlog_id)->setParameter('user_id', $user->getId())->getQuery();
            $result = $query->getResult();
            if (empty($result)) {
                /* @var $author \App\Entity\User */
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

    /**
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     *
     * @Route("/q/{username}", name="questlogs_byauthor", methods={"GET"})
     */
    public function byauthorAction($username)
    {
        return $this->redirect($this->generateUrl('questlogs_list', ['type' => 'find', 'author' => $username]));
    }

    /**
     * @param int $page
     *
     * @return Response
     *
     * @Route(
     *     "/questlogs/{type}/{page}",
     *     name="questlogs_list",
     *     methods={"GET"},
     *     requirements={"page"="\d+"},
     *     defaults={"type"="popular", "page"=1}
     * )
     */
    public function listAction($type, $page = 1, Request $request, QuestLogManager $questLogManager)
    {
        $response = new Response();
        $response->setPublic();
        $response->setMaxAge($this->cacheExpiration);
        /**
         * @var QuestLogManager $questlog_manager
         */
        $questlog_manager = $questLogManager;
        $questlog_manager->setLimit(30);
        $questlog_manager->setPage($page);
        $header = '';
        switch ($type) {
            case 'find':
                $pagetitle = 'Quest Log search results';
                $header = $this->searchForm($request);
                $questlog_manager->setUser($this->getUser());
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
                $pagetitle = 'Favorite Quest Logs';
                break;
            case 'mine':
                $response->setPrivate();
                $user = $this->getUser();
                if ($user) {
                    $paginator = $questlog_manager->findQuestLogsByAuthor($user);
                } else {
                    $paginator = $questlog_manager->getEmptyList();
                }
                $pagetitle = 'My Public Quest Logs';
                break;
            case 'recent':
                $paginator = $questlog_manager->findQuestLogsByAge();
                $pagetitle = 'Recent Quest Logs';
                break;
            case 'halloffame':
                $paginator = $questlog_manager->findQuestLogsInHallOfFame();
                $pagetitle = 'Hall of Fame';
                break;
            case 'hottopics':
                $paginator = $questlog_manager->findQuestLogsInHotTopic();
                $pagetitle = 'Hot Topics';
                break;
            case 'popular':
            default:
                $paginator = $questlog_manager->findQuestLogsByPopularity();
                $pagetitle = 'Popular Quest Logs';
                break;
        }
        $this->setSnapshots($paginator);

        return $this->render('QuestLog/public-questlogs.html.twig', ['pagetitle' => $pagetitle, 'pagedescription' => 'Browse the collection of thousands of premade decks.', 'questlogs' => $paginator, 'url' => $request->getRequestUri(), 'header' => $header, 'type' => $type, 'pages' => $questlog_manager->getClosePages(), 'prevurl' => $questlog_manager->getPreviousUrl(), 'nexturl' => $questlog_manager->getNextUrl()], $response);
    }
}
