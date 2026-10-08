<?php

declare(strict_types=1);

namespace App\Controller\Fellowship;

use App\Controller\CurrentUserTrait;
use App\Entity\Fellowship;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Attribute\Route;

class EditFellowshipController extends AbstractController
{
    use CurrentUserTrait;

    #[Route(path: '/fellowship/edit/{fellowship_id}', name: 'fellowship_edit', requirements: ['fellowship_id' => '\d+'], methods: ['GET'])]
    public function __invoke(#[MapEntity(id: 'fellowship_id', message: 'This fellowship does not exists.')] Fellowship $fellowship): Response
    {
        $response = new Response();
        /* @var $user User */
        $user = $this->currentUser();
        if (!$fellowship->getUser()->isEqualTo($user)) {
            throw new AccessDeniedHttpException('Access denied to this object.');
        }

        $data = ['pagetitle' => 'Edit Fellowship', 'deck1' => null, 'deck2' => null, 'deck3' => null, 'deck4' => null, 'fellowship' => $fellowship, 'is_public' => $fellowship->getIsPublic()];
        /* @var $fellowship_decks \App\Entity\FellowshipDeck[] */
        $fellowship_decks = $fellowship->getDecks();
        foreach ($fellowship_decks as $fellowship_deck) {
            $data['deck'.$fellowship_deck->getDeckNumber()] = $fellowship_deck->getDeck();
        }

        /* @var $fellowship_decks \App\Entity\FellowshipDecklist[] */
        $fellowship_decklists = $fellowship->getDecklists();
        foreach ($fellowship_decklists as $fellowship_decklist) {
            $data['deck'.$fellowship_decklist->getDeckNumber()] = $fellowship_decklist->getDecklist();
        }

        return $this->render('Fellowship/edit.html.twig', $data, $response);
    }
}
