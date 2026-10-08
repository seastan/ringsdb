<?php

declare(strict_types=1);

namespace App\Controller\Questlog;

use App\Entity\User;
use App\Model\DeleteQuestlogDto;
use App\Repository\QuestlogRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Attribute\Route;

class DeleteQuestlogController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly QuestlogRepository $questlogRepository
    ) {
    }

    #[Route(path: '/questlog/delete', name: 'questlog_delete', methods: ['POST'])]
    public function __invoke(#[MapRequestPayload] DeleteQuestlogDto $payload = new DeleteQuestlogDto()): RedirectResponse
    {
        /* @var $user User */
        $user = $this->getUser();
        if (!$user instanceof \Symfony\Component\Security\Core\User\UserInterface) {
            throw new AccessDeniedHttpException('You must be logged in for this operation.');
        }

        $questlog_id = filter_var($payload->questlogId, FILTER_SANITIZE_NUMBER_INT);
        /* @var $questlog \App\Entity\Questlog */
        $questlog = $this->questlogRepository->find($questlog_id);
        if (!$questlog instanceof \App\Entity\Questlog) {
            return $this->redirect($this->generateUrl('myquestlogs_list'));
        }

        if (!$questlog->getUser()->isEqualTo($user)) {
            throw new AccessDeniedHttpException("You don't have access to this quest log.");
        }

        if ($questlog->getNbVotes() || $questlog->getNbfavorites() || $questlog->getNbcomments()) {
            $this->addFlash('error', "You can't delete a published quest log.");
        } else {
            /* @var $decks \App\Entity\QuestlogDeck[] */
            $decks = $questlog->getDecks();
            foreach ($decks as $deck) {
                $this->entityManager->remove($deck);
            }

            $this->entityManager->remove($questlog);
            $this->entityManager->flush();
        }

        return $this->redirect($this->generateUrl('myquestlogs_list'));
    }
}
