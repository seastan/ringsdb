<?php

declare(strict_types=1);

namespace App\Controller\Fellowship;

use App\Entity\User;
use App\Repository\FellowshipRepository;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

class FavoriteFellowshipController extends AbstractController
{
    public function __construct(private EntityManagerInterface $entityManager, private Connection $connection, private FellowshipRepository $fellowshipRepository)
    {
    }

    /**
     * @Route("/user/fellowship_favorite", name="fellowship_favorite", methods={"POST"})
     */
    public function __invoke(Request $request): Response
    {
        /* @var $user User */
        $user = $this->getUser();
        if (!$user) {
            throw new AccessDeniedHttpException('You must be logged in to comment.');
        }

        $fellowship_id = filter_var($request->get('id'), FILTER_SANITIZE_NUMBER_INT);
        /* @var $fellowship \App\Entity\Fellowship */
        $fellowship = $this->fellowshipRepository->find($fellowship_id);
        if (!$fellowship) {
            throw new NotFoundHttpException('Wrong id');
        }

        /* @var $author User */
        $author = $fellowship->getUser();
        $is_favorite = $this->connection->executeQuery("SELECT\n\t\t\t\tcount(*)\n\t\t\t\tFROM fellowship d\n\t\t\t\tJOIN fellowship_favorite f ON f.fellowship_id = d.id\n\t\t\t\tWHERE f.user_id = ?\n\t\t\t\tAND d.id = ?", [$user->getId(), $fellowship_id])->fetch(\PDO::FETCH_NUM)[0];
        if ($is_favorite) {
            $fellowship->setNbfavorites($fellowship->getNbFavorites() - 1);
            $fellowship->removeFavorite($user);
            $fellowship->setDateUpdate(new \DateTime());
            if (!$author->isEqualTo($user)) {
                $author->setReputation($author->getReputation() - 5);
            }
        } else {
            $fellowship->setNbfavorites($fellowship->getNbFavorites() + 1);
            $fellowship->addFavorite($user);
            $fellowship->setDateUpdate(new \DateTime());
            if (!$author->isEqualTo($user)) {
                $author->setReputation($author->getReputation() + 5);
            }
        }

        $this->entityManager->flush();

        return new Response((string) $fellowship->getNbFavorites());
    }
}
