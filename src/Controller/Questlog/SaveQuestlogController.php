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
use App\Helper\StringSanitizer;
use App\Model\SaveQuestlogDto;
use App\Repository\DecklistRepository;
use App\Repository\DeckRepository;
use App\Repository\QuestlogRepository;
use App\Repository\ScenarioRepository;
use App\Services\DeckSaver;
use App\Services\Texts;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\Routing\Attribute\Route;

class SaveQuestlogController extends AbstractController
{
    use CurrentUserTrait;

    public function __construct(
        private EntityManagerInterface $entityManager,
        private QuestlogRepository $questlogRepository,
        private Texts $texts,
        private ScenarioRepository $scenarioRepository,
        private DeckRepository $deckRepository,
        private DecklistRepository $decklistRepository,
        private DeckSaver $deckSaver
    ) {
    }

    #[Route(path: '/questlog/save', name: 'questlog_save', methods: ['POST'])]
    public function __invoke(#[MapRequestPayload] SaveQuestlogDto $payload = new SaveQuestlogDto()): Response
    {
        /* @var $user User */
        $user = $this->currentUser();
        $questlog_id = intval(filter_var($payload->questlogId, FILTER_SANITIZE_NUMBER_INT));
        if ($questlog_id) {
            /* @var $questlog \App\Entity\Questlog */
            $questlog = $this->questlogRepository->find($questlog_id);
            if (!$questlog instanceof Questlog) {
                throw new NotFoundHttpException('This questlog does not exist.');
            }

            if (!$questlog->getUser()->isEqualTo($user)) {
                throw new AccessDeniedHttpException('Access denied to this object.');
            }
        } else {
            $questlog = new Questlog($user);
            $questlog->setNbVotes(0);
            $questlog->setNbComments(0);
            $questlog->setNbFavorites(0);
            $questlog->setNbDecks(0);
        }

        $name = trim(StringSanitizer::sanitize($payload->name, false));
        $name = substr($name, 0, 250);
        if (empty($name)) {
            $name = 'Untitled Questlog';
        }

        $descriptionMd = trim((string) $payload->descriptionMd);
        $descriptionHtml = $this->texts->markdown($descriptionMd);
        $quest = intval(filter_var($payload->quest, FILTER_SANITIZE_NUMBER_INT));
        $date = trim(StringSanitizer::sanitize($payload->date, false));
        $difficulty = trim(StringSanitizer::sanitize($payload->difficulty, false));
        $victory = trim(StringSanitizer::sanitize($payload->victory, false));
        $score = intval(filter_var($payload->score, FILTER_SANITIZE_NUMBER_INT));
        $public = boolval(filter_var($payload->public, FILTER_SANITIZE_NUMBER_INT));
        $victory = 'no' !== $victory;
        $difficulty = in_array($difficulty, ['normal', 'easy', 'nightmare'], true) ? $difficulty : 'normal';
        /* @var $scenario Scenario */
        $scenario = $this->scenarioRepository->find($quest);
        if (!$scenario instanceof Scenario) {
            throw new NotFoundHttpException('This scenario does not exists.');
        }

        $date = new \DateTime($date);
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
            for ($i = 1; $i <= SaveQuestlogDto::NB_DECKS; ++$i) {
                $slot = $payload->deck($i);
                $deck_id = intval(filter_var($slot['id'], FILTER_SANITIZE_NUMBER_INT));
                $is_decklist = 'true' === StringSanitizer::sanitize($slot['isDecklist']);
                $player = trim(StringSanitizer::sanitize($slot['playerName'], false));
                $content = (array) json_decode($slot['questlogdeckContent'], true);
                if ($deck_id) {
                    if (!$is_decklist) {
                        /* @var $deck \App\Entity\Deck */
                        $deck = $this->deckRepository->find($deck_id);
                        if (!$deck instanceof Deck) {
                            throw new NotFoundHttpException('One of the selected decks does not exist.');
                        }

                        $is_owner = $deck->getUser()->isEqualTo($user);
                        if (!$is_owner && !$deck->getUser()->getIsShareDecks()) {
                            throw new AccessDeniedHttpException('You are not allowed to view this deck. To get access, you can ask the deck owner to enable "Share my decks" on their account.');
                        }

                        if (!$is_owner) {
                            $deck = $this->deckSaver->cloneDeck($user, $deck);
                        }

                        // $content = (array) json_decode($request->get("deck".$i."_content"));
                        if (!isset($content['main']) || empty($content['main'])) {
                            return new Response('Cannot save a questlog with an empty deck');
                        }

                        $questlog_deck = new QuestlogDeck($questlog);
                        $questlog_deck->setDeck($deck);
                        $questlog_deck->setContent((string) json_encode($content));
                        $questlog_deck->setDeckNumber($i - $skip);
                        $questlog_deck->setPlayer($player);
                        $questlog->addDeck($questlog_deck);
                    } else {
                        /* @var $decklist Decklist */
                        $decklist = $this->decklistRepository->find($deck_id);
                        if (!$decklist instanceof Decklist) {
                            throw new NotFoundHttpException('One of the selected decks does not exist.');
                        }

                        // $content = (array) json_decode($request->get("deck".$i."_content"));
                        if (!isset($content['main']) || empty($content['main'])) {
                            return new Response('Cannot save a questlog with an empty deck');
                        }

                        $questlog_decklist = new QuestlogDeck($questlog);
                        $questlog_decklist->setDecklist($decklist);
                        $questlog_decklist->setDeck($decklist->getParent());
                        $questlog_decklist->setContent((string) json_encode($content));
                        $questlog_decklist->setDeckNumber($i - $skip);
                        $questlog_decklist->setPlayer($player);
                        $questlog->addDeck($questlog_decklist);
                    }

                    ++$nb_decks;
                } else {
                    // deck_id == 0 occurs if:
                    // 1. the deck slot in the builder is empty
                    // 2. the deck being referenced was deleted
                    $content = json_decode($slot['deckContent'], true);
                    if (!isset($content['main']) || empty($content['main'])) {
                        // Deck slot was empty
                        ++$skip;
                        continue;
                    }

                    // Reference deck was deleted
                    $questlog_deck = new QuestlogDeck($questlog);
                    $questlog_deck->setContent((string) json_encode($content));
                    $questlog_deck->setDeckNumber($i - $skip);
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
