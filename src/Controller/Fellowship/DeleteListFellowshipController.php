<?php

declare(strict_types=1);

namespace App\Controller\Fellowship;

use App\Entity\User;
use App\Model\DeleteListDto;
use App\Repository\FellowshipRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Attribute\Route;

class DeleteListFellowshipController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly FellowshipRepository $fellowshipRepository
    ) {
    }

    #[Route(path: '/fellowship/delete_list', name: 'fellowship_delete_list', methods: ['POST'])]
    public function __invoke(#[MapRequestPayload] DeleteListDto $payload = new DeleteListDto()): RedirectResponse
    {
        /* @var $user User */
        $user = $this->getUser();
        if (!$user instanceof \Symfony\Component\Security\Core\User\UserInterface) {
            throw new AccessDeniedHttpException('You must be logged in for this operation.');
        }

        $list_id = explode('-', $payload->ids);
        $message = null;
        foreach ($list_id as $id) {
            /* @var $fellowship \App\Entity\Fellowship */
            $fellowship = $this->fellowshipRepository->find($id);
            if (!$fellowship instanceof \App\Entity\Fellowship) {
                continue;
            }

            if (!$fellowship->getUser()->isEqualTo($user)) {
                continue;
            }

            if ($fellowship->getNbVotes() || $fellowship->getNbfavorites() || $fellowship->getNbcomments()) {
                $message = "You can't delete a published fellowship. Unpublished selected fellowships were deleted.";
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
            }
        }

        $this->entityManager->flush();
        $this->addFlash('notice', $message ?: 'Fellowships deleted.');

        return $this->redirect($this->generateUrl('myfellowships_list'));
    }
}
