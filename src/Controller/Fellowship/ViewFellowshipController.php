<?php

declare(strict_types=1);

namespace App\Controller\Fellowship;

use App\Repository\FellowshipRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

class ViewFellowshipController extends AbstractController
{
    public function __construct(
        private readonly FellowshipRepository $fellowshipRepository
    ) {
    }

    #[Route(path: '/fellowship/view/{fellowship_id}/{fellowship_name}', name: 'fellowship_view', requirements: ['fellowship_id' => '\d+'], defaults: ['fellowship_name' => null], methods: ['GET'])]
    public function __invoke(int $fellowship_id): Response
    {
        /* @var $fellowship \App\Entity\Fellowship */
        $fellowship = $this->fellowshipRepository->find($fellowship_id);
        if (!$fellowship instanceof \App\Entity\Fellowship) {
            throw new NotFoundHttpException('This fellowship does not exists.');
        }

        $is_owner = $this->getUser() instanceof \Symfony\Component\Security\Core\User\UserInterface && $this->getUser()->getId() == $fellowship->getUser()->getId();
        $is_public = $fellowship->getIsPublic();
        if (!$fellowship->getUser()->getIsShareDecks() && !$is_owner && !$is_public) {
            throw new AccessDeniedHttpException('You are not allowed to view this fellowship. To get access, you can ask it\'s owner to enable "Share my decks" on their account.');
        }

        if ($is_public) {
            $commenters = array_map(
                /* @var $comment \App\Entity\FellowshipComment */
                fn (\App\Entity\FellowshipComment $comment): string => $comment->getUser()->getUsername(),
                $fellowship->getComments()->getValues()
            );
            $commenters[] = $fellowship->getUser()->getUsername();
        } else {
            $commenters = [];
        }

        $data = ['pagetitle' => 'Fellowship', 'deck1' => null, 'deck2' => null, 'deck3' => null, 'deck4' => null, 'fellowship' => $fellowship, 'is_owner' => $is_owner, 'is_public' => $is_public, 'commenters' => $commenters];
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

        return $this->render('Fellowship/view.html.twig', $data);
    }
}
