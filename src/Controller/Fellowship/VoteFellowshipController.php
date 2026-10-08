<?php

declare(strict_types=1);

namespace App\Controller\Fellowship;

use App\Entity\User;
use App\Repository\FellowshipRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Attribute\Route;

class VoteFellowshipController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly FellowshipRepository $fellowshipRepository
    ) {
    }

    /*
     * records a user's vote
     */
    #[Route(path: '/user/fellowship_like', name: 'fellowship_like', methods: ['POST'])]
    public function __invoke(Request $request): Response
    {
        $user = $this->getUser();
        if (!$user instanceof \Symfony\Component\Security\Core\User\UserInterface) {
            throw new AccessDeniedHttpException('You must be logged in to comment.');
        }

        $fellowship_id = filter_var($request->request->get('id'), FILTER_SANITIZE_NUMBER_INT);
        /* @var $fellowship \App\Entity\Fellowship */
        $fellowship = $this->fellowshipRepository->find($fellowship_id);
        if (!$fellowship) {
            throw new BadRequestHttpException('Unable to find fellowship');
        }

        if (!$fellowship->getUser()->isEqualTo($user)) {
            $query = $this->fellowshipRepository->createQueryBuilder('d')->innerJoin('d.votes', 'u')->where('d.id = :fellowship_id')->andWhere('u.id = :user_id')->setParameter('fellowship_id', $fellowship_id)->setParameter('user_id', $user->getId())->getQuery();
            $result = $query->getResult();
            if (empty($result)) {
                /* @var $author User */
                $author = $fellowship->getUser();
                $author->setReputation($author->getReputation() + 1);
                $fellowship->addVote($user);
                $fellowship->setDateUpdate(new \DateTime());
                $fellowship->setNbVotes($fellowship->getNbVotes() + 1);
                $this->entityManager->flush();
            }
        }

        return new Response((string) $fellowship->getNbVotes());
    }
}
