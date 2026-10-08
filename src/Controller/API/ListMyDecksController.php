<?php

declare(strict_types=1);

namespace App\Controller\API;

use App\Entity\Deck;
use App\Entity\Decklist;
use App\Repository\DecklistRepository;
use App\Repository\DeckRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ListMyDecksController extends AbstractController
{
    public function __construct(
        private readonly DeckRepository $deckRepository,
        private readonly DecklistRepository $decklistRepository
    ) {
    }

    #[Route(path: '/api/private/decks', name: 'api_private_my_decks', methods: ['GET'])]
    public function __invoke(Request $request): Response
    {
        /* @var $decklists Decklist[] */
        $decklists = $this->decklistRepository->findBy(['user' => $this->getUser()], ['dateCreation' => 'DESC', 'id' => 'DESC']);
        foreach ($decklists as &$decklist) {
            $decklist->setDescriptionMd('');
        }

        /* @var $decks \App\Entity\Deck[] */
        $decks = $this->deckRepository->findBy(['user' => $this->getUser()], ['dateCreation' => 'DESC', 'id' => 'DESC']);
        foreach ($decks as &$deck) {
            $deck->setDescriptionMd('');
        }

        $decklists = array_merge($decklists, $decks);

        $dateUpdates = array_map(
            /* @var $deck \App\Entity\Deck */
            fn (\App\Entity\Decklist|Deck $deck): \DateTime => $deck->getDateUpdate(),
            $decklists
        );

        $response = new JsonResponse();

        if (count($dateUpdates)) {
            $response->setLastModified(max($dateUpdates));
            if ($response->isNotModified($request)) {
                return $response;
            }
        }

        $response->setData($decklists);

        return $response;
    }
}
