<?php

declare(strict_types=1);

namespace App\Controller\Questlog;

use App\Controller\CurrentUserTrait;
use App\Entity\Questlog;
use App\Entity\Scenario;
use App\Entity\User;
use App\Repository\ScenarioRepository;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Attribute\Route;

class EditQuestlogController extends AbstractController
{
    use CurrentUserTrait;

    public function __construct(
        private ScenarioRepository $scenarioRepository
    ) {
    }

    #[Route(path: '/questlog/edit/{questlog_id}', name: 'questlog_edit', requirements: ['questlog_id' => '\d+'], methods: ['GET'])]
    public function __invoke(#[MapEntity(id: 'questlog_id', message: 'This questlog does not exists.')] Questlog $questlog): Response
    {
        $response = new Response();
        /* @var $user User */
        $user = $this->currentUser();
        if (!$questlog->getUser()->isEqualTo($user)) {
            throw new AccessDeniedHttpException('Access denied to this object.');
        }

        /* @var $quests Scenario[] */
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
}
