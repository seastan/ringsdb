<?php

declare(strict_types=1);

namespace App\Controller\Fellowship;

use App\Entity\User;
use App\Repository\FellowshipRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Attribute\Route;

class DeleteFellowshipController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly FellowshipRepository $fellowshipRepository
    ) {
    }

    #[Route(path: '/fellowship/delete', name: 'fellowship_delete', methods: ['POST'])]
    public function __invoke(Request $request): RedirectResponse
    {
        /* @var $user User */
        $user = $this->getUser();
        if (!$user instanceof \Symfony\Component\Security\Core\User\UserInterface) {
            throw new AccessDeniedHttpException('You must be logged in for this operation.');
        }

        $fellowship_id = filter_var($request->request->get('fellowship_id'), FILTER_SANITIZE_NUMBER_INT);
        /* @var $fellowship \App\Entity\Fellowship */
        $fellowship = $this->fellowshipRepository->find($fellowship_id);
        if (!$fellowship instanceof \App\Entity\Fellowship) {
            return $this->redirect($this->generateUrl('myfellowships_list'));
        }

        if (!$fellowship->getUser()->isEqualTo($user)) {
            throw new AccessDeniedHttpException("You don't have access to this fellowship.");
        }

        if ($fellowship->getNbVotes() || $fellowship->getNbfavorites() || $fellowship->getNbcomments()) {
            $this->addFlash('error', "You can't delete a published fellowship.");
        } else {
            /* @var $decks \App\Entity\FellowshipDeck[] */
            $decks = $fellowship->getDecks();
            foreach ($decks as $deck) {
                $this->entityManager->remove($deck);
            }

            /* @var $decks \App\Entity\FellowshipDecklist[] */
            $decklists = $fellowship->getDecklists();
            foreach ($decklists as $decklist) {
                $this->entityManager->remove($decklist);
            }

            $this->entityManager->remove($fellowship);
            $this->entityManager->flush();
        }

        return $this->redirect($this->generateUrl('myfellowships_list'));
    }
}
