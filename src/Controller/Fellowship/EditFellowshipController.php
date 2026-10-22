<?php

declare(strict_types=1);

namespace App\Controller\Fellowship;

use App\Controller\CurrentUserTrait;
use App\Entity\User;
use App\Repository\FellowshipRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

class EditFellowshipController extends AbstractController
{
    use CurrentUserTrait;

    private FellowshipRepository $fellowshipRepository;

    public function __construct(
        FellowshipRepository $fellowshipRepository
    ) {
        $this->fellowshipRepository = $fellowshipRepository;
    }

    /**
     * @Route(
     *     "/fellowship/edit/{fellowship_id}",
     *     name="fellowship_edit",
     *     methods={"GET"},
     *     requirements={"fellowship_id"="\d+"}
     * )
     */
    public function __invoke(int $fellowship_id): Response
    {
        $response = new Response();
        /* @var $user User */
        $user = $this->currentUser();
        /* @var $fellowship \App\Entity\Fellowship */
        $fellowship = $this->fellowshipRepository->find($fellowship_id);
        if (!$fellowship) {
            throw new NotFoundHttpException('This fellowship does not exists.');
        }

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
