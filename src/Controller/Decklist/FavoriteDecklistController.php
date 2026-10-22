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
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

class FavoriteDecklistController extends AbstractController
{
    private DecklistRepository $decklistRepository;

    private EntityManagerInterface $entityManager;

    public function __construct(
        EntityManagerInterface $entityManager,
        DecklistRepository $decklistRepository
    ) {
        $this->decklistRepository = $decklistRepository;
        $this->entityManager = $entityManager;
    }

    /**
     * adds a decklist to a user's list of favorites.
     *
     * @Route("/user/favorite", name="decklist_favorite", methods={"POST"})
     */
    public function __invoke(Request $request): Response
    {
        $user = $this->getUser();
        if (!$user) {
            throw new AccessDeniedHttpException('You must be logged in to comment.');
        }

        $decklist_id = filter_var($request->get('id'), FILTER_SANITIZE_NUMBER_INT);
        /* @var $decklist \App\Entity\Decklist */
        $decklist = $this->decklistRepository->find($decklist_id);
        if (!$decklist) {
            throw new NotFoundHttpException('Wrong id');
        }

        $author = $decklist->getUser();
        $dbh = $this->getDoctrine()->getConnection();
        $is_favorite = $dbh->executeQuery("SELECT\n\t\t\t\tcount(*)\n\t\t\t\tFROM decklist d\n\t\t\t\tJOIN favorite f ON f.decklist_id = d.id\n\t\t\t\tWHERE f.user_id = ?\n\t\t\t\tAND d.id = ?", [$user->getId(), $decklist_id])->fetch(\PDO::FETCH_NUM)[0];
        if ($is_favorite) {
            $decklist->setNbfavorites($decklist->getNbFavorites() - 1);
            $user->removeFavorite($decklist);
            if (!$author->isEqualTo($user)) {
                $author->setReputation($author->getReputation() - 5);
            }
        } else {
            $decklist->setNbfavorites($decklist->getNbFavorites() + 1);
            $user->addFavorite($decklist);
            $decklist->setDateUpdate(new \DateTime());
            if (!$author->isEqualTo($user)) {
                $author->setReputation($author->getReputation() + 5);
            }
        }

        $this->entityManager->flush();

        return new Response((string) $decklist->getNbFavorites());
    }
}
