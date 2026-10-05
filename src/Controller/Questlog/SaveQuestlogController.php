<?php

declare(strict_types=1);

namespace App\Controller\Questlog;

use App\Controller\CurrentUserTrait;
use App\Entity\Deck;
use App\Entity\Decklist;
use App\Entity\Questlog;
use App\Entity\QuestlogDeck;
use App\Entity\Scenario;
use App\Entity\User;
use App\Repository\DecklistRepository;
use App\Repository\DeckRepository;
use App\Repository\QuestlogRepository;
use App\Repository\ScenarioRepository;
use App\Services\Decks;
use App\Services\Texts;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\Routing\Annotation\Route;

class SaveQuestlogController extends AbstractController
{
    use CurrentUserTrait;

    private EntityManagerInterface $entityManager;

    private QuestlogRepository $questlogRepository;

    private Texts $texts;

    private ScenarioRepository $scenarioRepository;

    private DeckRepository $deckRepository;

    private Decks $decks;

    private DecklistRepository $decklistRepository;

    public function __construct(
        EntityManagerInterface $entityManager,
        QuestlogRepository $questlogRepository,
        Texts $texts,
        ScenarioRepository $scenarioRepository,
        DeckRepository $deckRepository,
        DecklistRepository $decklistRepository,
        Decks $decks
    ) {
        $this->entityManager = $entityManager;
        $this->questlogRepository = $questlogRepository;
        $this->texts = $texts;
        $this->scenarioRepository = $scenarioRepository;
        $this->deckRepository = $deckRepository;
        $this->decks = $decks;
        $this->decklistRepository = $decklistRepository;
    }

    /**
     * @Route("/questlog/save", name="questlog_save", methods={"POST"})
     */
    public function __invoke(Request $request): Response
    {
        /* @var $user User */
        $user = $this->currentUser();
        $questlog_id = intval(filter_var($request->request->get('questlog_id'), FILTER_SANITIZE_NUMBER_INT));
        if ($questlog_id) {
            /* @var $questlog \App\Entity\Questlog */
            $questlog = $this->questlogRepository->find($questlog_id);
            if (!$questlog) {
                throw new NotFoundHttpException('This questlog does not exist.');
            }

            if ($questlog->getUser() && !$questlog->getUser()->isEqualTo($user)) {
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

        $descriptionMd = trim((string) $request->request->get('descriptionMd'));
        $descriptionHtml = $this->texts->markdown($descriptionMd);
        $quest = intval(filter_var($request->request->get('quest'), FILTER_SANITIZE_NUMBER_INT));
        $date = trim((string) filter_var($request->request->get('date'), FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES));
        $difficulty = trim((string) filter_var($request->request->get('difficulty'), FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES));
        $victory = trim((string) filter_var($request->request->get('victory'), FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES));
        $score = intval(filter_var($request->request->get('score'), FILTER_SANITIZE_NUMBER_INT));
        $public = boolval(filter_var($request->request->get('public'), FILTER_SANITIZE_NUMBER_INT));
        $victory = 'no' !== $victory;
        $difficulty = in_array($difficulty, ['normal', 'easy', 'nightmare'], true) ? $difficulty : 'normal';
        /* @var $scenario Scenario */
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
            $questlog->setIsPublic($public);
            if ($public) {
                $questlog->setDatePublish(new \DateTime());
            }

            $questlogdecks = $questlog->getDecks();
            foreach ($questlog->getDecks() as $deck) {
                $questlog->removeDeck($deck);
                $this->entityManager->remove($deck);
            }

            $nb_decks = 0;
            $skip = 0;
            for ($i = 1; $i <= 4; ++$i) {
                $deck_id = intval(filter_var($request->request->get('deck'.$i.'_id'), FILTER_SANITIZE_NUMBER_INT));
                $is_decklist = 'true' == filter_var($request->get('deck'.$i.'_is_decklist'), FILTER_SANITIZE_STRING);
                $player = trim((string) filter_var($request->get('questlogdeck'.$i.'_player_name'), FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES));
                $content = (array) json_decode($request->get('questlogdeck'.$i.'_content'), true);
                if ($deck_id) {
                    if (!$is_decklist) {
                        /* @var $deck \App\Entity\Deck */
                        $deck = $this->deckRepository->find($deck_id);
                        if (!$deck) {
                            throw new NotFoundHttpException('One of the selected decks does not exist.');
                        }

                        $is_owner = $deck->getUser()->isEqualTo($user);
                        if (!$is_owner && !$deck->getUser()->getIsShareDecks()) {
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
                        /* @var $decklist Decklist */
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

            if (0 === $nb_decks) {
                throw new UnprocessableEntityHttpException("You can't save an empty quest log.");
            }

            $questlog->setNbDecks($nb_decks);
        }

        $this->entityManager->persist($questlog);
        $this->entityManager->flush();

        return $this->redirect($this->generateUrl('questlog_view', ['questlog_id' => $questlog->getId(), 'questlog_name' => $questlog->getNameCanonical()]));
    }
}
