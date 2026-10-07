<?php

declare(strict_types=1);

namespace App\Controller\Fellowship;

use App\Controller\CurrentUserTrait;
use App\Entity\Deck;
use App\Entity\User;
use App\Repository\DeckRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class NewFellowshipController extends AbstractController
{
    use CurrentUserTrait;

    public function __construct(
        private DeckRepository $deckRepository
    ) {
    }

    /**
     * @Route(
     *     "/fellowship/new/{deck1_id}/{deck2_id}/{deck3_id}/{deck4_id}",
     *     name="fellowship_new",
     *     methods={"GET"},
     *     requirements={"deck1_id"="\d+", "deck2_id"="\d+", "deck3_id"="\d+", "deck4_id"="\d+"},
     *     defaults={"deck1_id"=null, "deck2_id"=null, "deck3_id"=null, "deck4_id"=null}
     * )
     */
    public function __invoke(int $deck1_id, int $deck2_id, int $deck3_id, int $deck4_id): Response
    {
        $response = new Response();
        $decks = [];
        $deck_ids = func_get_args();
        for ($i = 0; $i < 4; ++$i) {
            $decks[$i] = null;
            if ($deck_ids[$i]) {
                /* @var $decks Deck[] */
                $decks[$i] = $this->deckRepository->find($deck_ids[$i]);
                if ($decks[$i] instanceof Deck) {
                    /* @var $user User */
                    $user = $decks[$i]->getUser();
                    if (!$user->getIsShareDecks() && !$this->currentUser()->isEqualTo($user)) {
                        $decks[$i] = null;
                    }
                }
            }
        }

        return $this->render('Fellowship/edit.html.twig', ['pagetitle' => 'Create a Fellowship', 'deck1' => $decks[0], 'deck2' => $decks[1], 'deck3' => $decks[2], 'deck4' => $decks[3], 'is_public' => false], $response);
    }
}
