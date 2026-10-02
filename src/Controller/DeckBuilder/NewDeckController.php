<?php

namespace App\Controller\DeckBuilder;

use App\Controller\CurrentUserTrait;
use App\Entity\Deck;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Annotation\Route;

class NewDeckController extends AbstractController
{
    use CurrentUserTrait;

    private EntityManagerInterface $entityManager;

    public function __construct(
        EntityManagerInterface $entityManager
    ) {
        $this->entityManager = $entityManager;
    }

    /**
     * @Route("/deck/new", name="deck_buildform", methods={"GET"})
     */
    public function newAction(): RedirectResponse
    {
        /* @var $deck \App\Entity\Deck */
        $deck = new Deck();
        $deck->setName('New Deck');
        $deck->setDescriptionMd('');
        $deck->setLastPack(null);
        $deck->setProblem('too_few_heroes');
        $deck->setTags('');
        $deck->setUser($this->currentUser());
        $this->entityManager->persist($deck);
        $this->entityManager->flush();

        return $this->redirect($this->generateUrl('deck_edit', ['deck_id' => $deck->getId()]));
    }
}
