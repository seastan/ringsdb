<?php

declare(strict_types=1);

namespace App\Controller\API;

use App\Controller\CurrentUserTrait;
use App\Entity\Deck;
use App\Entity\Decklist;
use App\Entity\User;
use App\Repository\DecklistRepository;
use App\Repository\DeckRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ListUserDecksController extends AbstractController
{
    use CurrentUserTrait;

    public function __construct(
        private DeckRepository $deckRepository,
        private DecklistRepository $decklistRepository
    ) {
    }

    #[Route(path: '/api/private/decks_by_user/{username}', name: 'api_private_user_decks', methods: ['GET'])]
    public function __invoke(Request $request, UserRepository $userRepository, string $username): Response
    {
        /* @var $em EntityManager */
        /* @var $user User */
        $user = $userRepository->findOneBy(['username' => $username]);
        if (!$user instanceof User) {
            return new JsonResponse(['success' => false, 'error' => 'This user does not exist.']);
        }

        $show_private_decks = $this->currentUser()->isEqualTo($user);
        /* @var $decklists Decklist[] */
        $decklists = $this->decklistRepository->findBy(['user' => $user], ['dateCreation' => 'DESC', 'id' => 'DESC']);
        foreach ($decklists as &$decklist) {
            $decklist->setDescriptionMd('');
        }

        if ($show_private_decks) {
            /* @var $decks \App\Entity\Deck[] */
            $decks = $this->deckRepository->findBy(['user' => $user], ['dateCreation' => 'DESC', 'id' => 'DESC']);
            foreach ($decks as &$deck) {
                $deck->setDescriptionMd('');
            }

            $decklists = array_merge($decklists, $decks);
        }

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
