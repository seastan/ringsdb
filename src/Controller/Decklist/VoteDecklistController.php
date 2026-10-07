<?php

declare(strict_types=1);

namespace App\Controller\Decklist;

use App\Entity\Decklist;
use App\Repository\DecklistRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Attribute\Route;

class VoteDecklistController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly DecklistRepository $decklistRepository
    ) {
    }

    /**
     * records a user's vote.
     */
    #[Route(path: '/user/like', name: 'decklist_like', methods: ['POST'])]
    public function __invoke(Request $request): Response
    {
        $user = $this->getUser();
        if (!$user instanceof \Symfony\Component\Security\Core\User\UserInterface) {
            throw new AccessDeniedHttpException('You must be logged in to comment.');
        }

        $decklist_id = filter_var($request->get('id'), FILTER_SANITIZE_NUMBER_INT);
        /* @var $decklist \App\Entity\Decklist */
        $decklist = $this->decklistRepository->find($decklist_id);
        if (!$decklist instanceof Decklist) {
            throw new BadRequestHttpException('Unable to find deck');
        }

        if (!$decklist->getUser()->isEqualTo($user)) {
            $query = $this->decklistRepository->createQueryBuilder('d')->innerJoin('d.votes', 'u')->where('d.id = :decklist_id')->andWhere('u.id = :user_id')->setParameter('decklist_id', $decklist_id)->setParameter('user_id', $user->getId())->getQuery();
            $result = $query->getResult();
            if (empty($result)) {
                $author = $decklist->getUser();
                $author->setReputation($author->getReputation() + 1);
                $user->addVote($decklist);
                $decklist->setDateUpdate(new \DateTime());
                $decklist->setNbVotes($decklist->getNbVotes() + 1);
                $this->entityManager->flush();
            }
        }

        return new Response((string) $decklist->getNbVotes());
    }
}
