<?php

declare(strict_types=1);

namespace App\Controller\Questlog;

use App\Entity\User;
use App\Repository\QuestlogRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;

class VoteQuestlogController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly QuestlogRepository $questlogRepository
    ) {
    }

    /**
     * records a user's vote.
     *
     * @Route("/user/questlog_like", name="questlog_like", methods={"POST"})
     */
    public function __invoke(Request $request): Response
    {
        $user = $this->getUser();
        if (!$user) {
            throw new AccessDeniedHttpException('You must be logged in to comment.');
        }

        $questlog_id = filter_var($request->get('id'), FILTER_SANITIZE_NUMBER_INT);
        /* @var $questlog \App\Entity\QuestLog */
        $questlog = $this->questlogRepository->find($questlog_id);
        if (!$questlog) {
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
