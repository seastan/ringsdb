<?php

declare(strict_types=1);

namespace App\Controller\Questlog;

use App\Entity\User;
use App\Model\IdDto;
use App\Repository\QuestlogRepository;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

class FavoriteQuestlogController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly QuestlogRepository $questlogRepository,
        private readonly Connection $connection
    ) {
    }

    #[Route(path: '/user/questlog_favorite', name: 'questlog_favorite', methods: ['POST'])]
    public function __invoke(#[MapRequestPayload] IdDto $payload = new IdDto()): Response
    {
        /* @var $user User */
        $user = $this->getUser();
        if (!$user instanceof \Symfony\Component\Security\Core\User\UserInterface) {
            throw new AccessDeniedHttpException('You must be logged in to comment.');
        }

        $questlog_id = filter_var($payload->id, FILTER_SANITIZE_NUMBER_INT);
        /* @var $questlog \App\Entity\QuestLog */
        $questlog = $this->questlogRepository->find($questlog_id);
        if (!$questlog instanceof \App\Entity\Questlog) {
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
