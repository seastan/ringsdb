<?php

declare(strict_types=1);

namespace App\Controller\Questlog;

use App\Entity\User;
use App\Model\DeleteListDto;
use App\Repository\QuestlogRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Attribute\Route;

class DeleteListQuestlogController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly QuestlogRepository $questlogRepository
    ) {
    }

    #[Route(path: '/questlog/delete_list', name: 'questlog_delete_list', methods: ['POST'])]
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
            /* @var $questlog \App\Entity\Questlog */
            $questlog = $this->questlogRepository->find($id);
            if (!$questlog instanceof \App\Entity\Questlog) {
                continue;
            }

            if (!$questlog->getUser()->isEqualTo($user)) {
                continue;
            }

            if ($questlog->getNbVotes() || $questlog->getNbfavorites() || $questlog->getNbcomments()) {
                $message = "You can't delete a published quest log. Unpublished selected quest logs were deleted.";
            } else {
                /* @var $decks \App\Entity\QuestlogDeck[] */
                $decks = $questlog->getDecks();
                foreach ($decks as $deck) {
                    $this->entityManager->remove($deck);
                }

                $this->entityManager->remove($questlog);
            }
        }

        $this->entityManager->flush();
        $this->addFlash('notice', $message ?: 'Quest Logs deleted.');

        return $this->redirect($this->generateUrl('myquestlogs_list'));
    }
}
