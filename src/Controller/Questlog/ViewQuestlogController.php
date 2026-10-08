<?php

declare(strict_types=1);

namespace App\Controller\Questlog;

use App\Repository\QuestlogRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

class ViewQuestlogController extends AbstractController
{
    public function __construct(
        private readonly QuestlogRepository $questlogRepository
    ) {
    }

    #[Route(path: '/questlog/view/{questlog_id}/{questlog_name}', name: 'questlog_view', requirements: ['questlog_id' => '\d+'], defaults: ['questlog_name' => null], methods: ['GET'])]
    public function __invoke(int $questlog_id): Response
    {
        /* @var $questlog \App\Entity\Questlog */
        $questlog = $this->questlogRepository->find($questlog_id);
        if (!$questlog instanceof \App\Entity\Questlog) {
            throw new NotFoundHttpException('This questlog does not exists.');
        }

        $is_owner = $this->getUser() instanceof \Symfony\Component\Security\Core\User\UserInterface && $this->getUser()->getId() == $questlog->getUser()->getId();
        $is_public = $questlog->getIsPublic();
        if (!$questlog->getUser()->getIsShareDecks() && !$is_owner && !$is_public) {
            throw new AccessDeniedHttpException('You are not allowed to view this questlog. To get access, you can ask it\'s owner to enable "Share my decks" on their account.');
        }

        if ($is_public) {
            $commenters = array_map(
                /* @var $comment \App\Entity\QuestlogComment */
                fn (\App\Entity\QuestlogComment $comment): string => $comment->getUser()->getUsername(),
                $questlog->getComments()->getValues()
            );
            $commenters[] = $questlog->getUser()->getUsername();
        } else {
            $commenters = [];
        }

        $data = [
            'pagetitle' => ($questlog->getScenario() ? $questlog->getScenario()->getName() : 'Unknown Scenario').' - Quest Log',
            'deck1' => null,
            'deck2' => null,
            'deck3' => null,
            'deck4' => null,
            'questlogdeck1_content' => null,
            'questlogdeck2_content' => null,
            'questlogdeck3_content' => null,
            'questlogdeck4_content' => null,
            'questlogdeck1_player_name' => null,
            'questlogdeck2_player_name' => null,
            'questlogdeck3_player_name' => null,
            'questlogdeck4_player_name' => null,
            'questlog' => $questlog,
            'is_owner' => $is_owner,
            'is_public' => $is_public,
            'commenters' => $commenters,
            'nbDecks' => $questlog->getNbDecks(),
        ];
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
}
