<?php

declare(strict_types=1);

namespace App\Controller\Questlog;

use App\Entity\User;
use App\Repository\QuestlogRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Annotation\Route;

class DeleteQuestlogController extends AbstractController
{
    private EntityManagerInterface $entityManager;

    private QuestlogRepository $questlogRepository;

    public function __construct(
        EntityManagerInterface $entityManager,
        QuestlogRepository $questlogRepository
    ) {
        $this->entityManager = $entityManager;
        $this->questlogRepository = $questlogRepository;
    }

    /**
     * @Route("/questlog/delete", name="questlog_delete", methods={"POST"})
     */
    public function __invoke(Request $request): RedirectResponse
    {
        /* @var $user User */
        $user = $this->getUser();
        if (!$user) {
            throw new AccessDeniedHttpException('You must be logged in for this operation.');
        }

        $questlog_id = filter_var($request->get('questlog_id'), FILTER_SANITIZE_NUMBER_INT);
        /* @var $questlog \App\Entity\Questlog */
        $questlog = $this->questlogRepository->find($questlog_id);
        if (!$questlog) {
            return $this->redirect($this->generateUrl('myquestlogs_list'));
        }

        if (!$questlog->getUser()->isEqualTo($user)) {
            throw new AccessDeniedHttpException("You don't have access to this quest log.");
        }

        if ($questlog->getNbVotes() || $questlog->getNbfavorites() || $questlog->getNbcomments()) {
            $this->get('session')->getFlashBag()->set('error', "You can't delete a published quest log.");
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
