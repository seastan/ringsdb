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
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ApiPrivateController extends AbstractController
{
    use CurrentUserTrait;
    /**
     * @var DeckRepository
     */
    private $deckRepository;
    /**
     * @var DecklistRepository
     */
    private $decklistRepository;

    public function __construct(DeckRepository $deckRepository, DecklistRepository $decklistRepository)
    {
        $this->deckRepository = $deckRepository;
        $this->decklistRepository = $decklistRepository;
    }

    /**
     * @Route("/api/private/decks", name="api_private_my_decks", methods={"GET"})
     */
    public function listDecksAction(Request $request): Response
    {
        $response = new Response();
        /* @var $em EntityManager */
        $em = $this->getDoctrine()->getManager();
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
        $dateUpdates = array_map(function ($deck) {
            /* @var $deck \App\Entity\Deck */
            return $deck->getDateUpdate();
        }, $decklists);
        if (count($dateUpdates)) {
            $response->setLastModified(max($dateUpdates));
            if ($response->isNotModified($request)) {
                return $response;
            }
        }
        $content = json_encode($decklists);
        $response->headers->set('Content-Type', 'application/json');
        $response->setContent($content);

        return $response;
    }

    /**
     * @Route("/api/private/decks_by_user/{username}", name="api_private_user_decks", methods={"GET"})
     */
    public function listUserDecksAction($username, Request $request, UserRepository $userRepository): Response
    {
        $response = new Response();
        /* @var $em EntityManager */
        $em = $this->getDoctrine()->getManager();
        /* @var $user User */
        $user = $userRepository->findOneBy(['username' => $username]);
        if (!$user) {
            $content = json_encode(['success' => false, 'error' => 'This user does not exist.']);
            $response->headers->set('Content-Type', 'application/json');
            $response->setContent($content);

            return $response;
        }
        $show_private_decks = $user->getId() == $this->currentUser()->getId();
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
        $dateUpdates = array_map(function ($deck) {
            /* @var $deck \App\Entity\Deck */
            return $deck->getDateUpdate();
        }, $decklists);
        if (count($dateUpdates)) {
            $response->setLastModified(max($dateUpdates));
            if ($response->isNotModified($request)) {
                return $response;
            }
        }
        $content = json_encode($decklists);
        $response->headers->set('Content-Type', 'application/json');
        $response->setContent($content);

        return $response;
    }

    /*
     * Get the description of one Deck of the authenticated user
     */
    /**
     * @Route(
     *     "/api/private/deck/load/{id}",
     *     name="api_private_load_deck",
     *     methods={"GET"},
     *     requirements={"id"="\d+"}
     * )
     */
    public function loadDeckAction($id, Request $request): Response
    {
        $response = new Response();
        /* @var $em EntityManager */
        $em = $this->getDoctrine()->getManager();
        /* @var $deck \App\Entity\Deck */
        $deck = $this->deckRepository->find($id);
        if (!$deck) {
            $content = json_encode(['success' => false, 'error' => 'This deck does not exists.']);
            $response->headers->set('Content-Type', 'application/json');
            $response->setContent($content);

            return $response;
        }
        /* @var $user User */
        $user = $deck->getUser();
        if (!$user->getIsShareDecks() && $user != $this->getUser()) {
            $content = json_encode(['success' => false, 'error' => 'You are not allowed to view this deck. To get access, you can ask the deck owner to enable "Share my decks" on their account.']);
            $response->headers->set('Content-Type', 'application/json');
            $response->setContent($content);

            return $response;
        }
        $response->setLastModified($deck->getDateUpdate());
        if ($response->isNotModified($request)) {
            return $response;
        }
        $content = json_encode($deck);
        $response->headers->set('Content-Type', 'application/json');
        $response->setContent($content);

        return $response;
    }
}
