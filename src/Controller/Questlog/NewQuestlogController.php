<?php

declare(strict_types=1);

namespace App\Controller\Questlog;

use App\Controller\CurrentUserTrait;
use App\Entity\Questlog;
use App\Entity\Scenario;
use App\Repository\DecklistRepository;
use App\Repository\DeckRepository;
use App\Repository\ScenarioRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class NewQuestlogController extends AbstractController
{
    use CurrentUserTrait;

    private ScenarioRepository $scenarioRepository;
    private DecklistRepository $decklistRepository;
    private DeckRepository $deckRepository;

    public function __construct(
        ScenarioRepository $scenarioRepository,
        DecklistRepository $decklistRepository,
        DeckRepository $deckRepository
    ) {
        $this->scenarioRepository = $scenarioRepository;
        $this->decklistRepository = $decklistRepository;
        $this->deckRepository = $deckRepository;
    }

    /**
     * @Route(
     *     "/questlog/new/{public}/{deck1_id}/{deck2_id}/{deck3_id}/{deck4_id}",
     *     name="questlog_new",
     *     methods={"GET"},
     *     requirements={"public"="\d+", "deck1_id"="\d+", "deck2_id"="\d+", "deck3_id"="\d+", "deck4_id"="\d+"},
     *     defaults={"public"=0, "deck1_id"=null, "deck2_id"=null, "deck3_id"=null, "deck4_id"=null}
     * )
     */
    public function __invoke(int $deck1_id, int $deck2_id, int $deck3_id, int $deck4_id, int $public): Response
    {
        $response = new Response();
        /* @var $quests Scenario[] */
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
}
