<?php

declare(strict_types=1);

namespace App\Controller\Questlog;

use App\Entity\User;
use App\Repository\QuestlogRepository;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

class FavoriteQuestlogController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly QuestlogRepository $questlogRepository,
        private readonly Connection $connection
    ) {
    }

    /**
     * @Route("/user/questlog_favorite", name="questlog_favorite", methods={"POST"})
     */
    public function __invoke(Request $request): Response
    {
        /* @var $user User */
        $user = $this->getUser();
        if (!$user) {
            throw new AccessDeniedHttpException('You must be logged in to comment.');
        }

        $questlog_id = filter_var($request->get('id'), FILTER_SANITIZE_NUMBER_INT);
        /* @var $questlog \App\Entity\QuestLog */
        $questlog = $this->questlogRepository->find($questlog_id);
        if (!$questlog) {
            throw new NotFoundHttpException('Wrong id');
        }

        /* @var $author User */
        $author = $questlog->getUser();
        $is_favorite = $this->connection->executeQuery("SELECT\n\t\t\t\tcount(*)\n\t\t\t\tFROM questlog d\n\t\t\t\tJOIN questlog_favorite f ON f.questlog_id = d.id\n\t\t\t\tWHERE f.user_id = ?\n\t\t\t\tAND d.id = ?", [$user->getId(), $questlog_id])->fetchOne();
        if ($is_favorite) {
            $questlog->setNbfavorites($questlog->getNbFavorites() - 1);
            $questlog->removeFavorite($user);
            $questlog->setDateUpdate(new \DateTime());
            if (!$author->isEqualTo($user)) {
                $author->setReputation($author->getReputation() - 5);
            }
        } else {
            $questlog->setNbfavorites($questlog->getNbFavorites() + 1);
            $questlog->addFavorite($user);
            $questlog->setDateUpdate(new \DateTime());
            if (!$author->isEqualTo($user)) {
                $author->setReputation($author->getReputation() + 5);
            }
        }

        $this->entityManager->flush();

        return new Response((string) $questlog->getNbFavorites());
    }
}
