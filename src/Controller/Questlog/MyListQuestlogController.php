<?php

declare(strict_types=1);

namespace App\Controller\Questlog;

use App\Controller\CurrentUserTrait;
use App\Entity\Scenario;
use App\Entity\User;
use App\Repository\QuestlogRepository;
use App\Repository\ScenarioRepository;
use App\Services\SnapshotManager;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

class MyListQuestlogController extends AbstractController
{
    use CurrentUserTrait;

    private ScenarioRepository $scenarioRepository;
    private Connection $connection;
    private QuestlogRepository $questlogRepository;
    private SnapshotManager $snapshotManager;

    public function __construct(
        Connection $connection,
        ScenarioRepository $scenarioRepository,
        QuestlogRepository $questlogRepository,
        SnapshotManager $snapshotManager
    ) {
        $this->scenarioRepository = $scenarioRepository;
        $this->connection = $connection;
        $this->questlogRepository = $questlogRepository;
        $this->snapshotManager = $snapshotManager;
    }

    /**
     * @Route(
     *     "/myquestlogs/{scenario_name_canonical}/{quest_mode}",
     *     name="myquestlogs_list",
     *     methods={"GET"},
     *     defaults={"scenario_name_canonical"=null, "quest_mode"="normal"}
     * )
     */
    public function __invoke($scenario_name_canonical, $quest_mode): Response
    {
        // $quest_mode = 'normal';
        /* @var $quests Scenario[] */
        $quests = $this->scenarioRepository->findBy([], ['position' => 'ASC']);
        /* @var $user User */
        $user = $this->getUser();
        if (!$user) {
            throw new AccessDeniedHttpException('You must be logged in for this operation.');
        }
        // Count played scenarios
        $playedEasy = [];
        $playedNormal = [];
        $playedNightmare = [];
        $played = $this->connection->executeQuery('SELECT DISTINCT scenario_id, quest_mode, sum(success) as victory FROM questlog WHERE user_id = ? GROUP BY scenario_id, quest_mode', [$user->getId()])->fetchAll(\PDO::FETCH_NAMED);
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
            /* @var $scenario Scenario */
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
        $this->snapshotManager->setSnapshots($questlogs);
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
}
