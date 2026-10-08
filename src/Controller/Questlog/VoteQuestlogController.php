<?php

declare(strict_types=1);

namespace App\Controller\Questlog;

use App\Entity\User;
use App\Model\IdDto;
use App\Repository\QuestlogRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Attribute\Route;

class VoteQuestlogController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly QuestlogRepository $questlogRepository
    ) {
    }

    /**
     * records a user's vote.
     */
    #[Route(path: '/user/questlog_like', name: 'questlog_like', methods: ['POST'])]
    public function __invoke(#[MapRequestPayload] IdDto $payload = new IdDto()): Response
    {
        $user = $this->getUser();
        if (!$user instanceof \Symfony\Component\Security\Core\User\UserInterface) {
            throw new AccessDeniedHttpException('You must be logged in to comment.');
        }

        $questlog_id = filter_var($payload->id, FILTER_SANITIZE_NUMBER_INT);
        /* @var $questlog \App\Entity\QuestLog */
        $questlog = $this->questlogRepository->find($questlog_id);
        if (!$questlog instanceof \App\Entity\Questlog) {
            throw new BadRequestHttpException('Unable to find quest log');
        }

        if (!$questlog->getUser()->isEqualTo($user)) {
            $query = $this->questlogRepository->createQueryBuilder('d')->innerJoin('d.votes', 'u')->where('d.id = :questlog_id')->andWhere('u.id = :user_id')->setParameter('questlog_id', $questlog_id)->setParameter('user_id', $user->getId())->getQuery();
            $result = $query->getResult();
            if (empty($result)) {
                $author = $questlog->getUser();
                $author->setReputation($author->getReputation() + 1);
                $questlog->addVote($user);
                $questlog->setDateUpdate(new \DateTime());
                $questlog->setNbVotes($questlog->getNbVotes() + 1);
                $this->entityManager->flush();
            }
        }

        return new Response((string) $questlog->getNbVotes());
    }
}
