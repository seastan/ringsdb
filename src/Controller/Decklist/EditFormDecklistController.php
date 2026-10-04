<?php

declare(strict_types=1);

namespace App\Controller\Decklist;

use App\Entity\Decklist;
use App\Repository\DecklistRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class EditFormDecklistController extends AbstractController
{
    private DecklistRepository $decklistRepository;

    public function __construct(
        DecklistRepository $decklistRepository
    ) {
        $this->decklistRepository = $decklistRepository;
    }

    /**
     * Displays the decklist edit form.
     *
     * @Route("/decklist/edit/{decklist_id}", name="decklist_edit", requirements={"decklist_id"="\d+"})
     */
    public function __invoke(int $decklist_id): Response
    {
        /* @var $user \App\Entity\User */
        $user = $this->getUser();
        if (!$user) {
            throw $this->createAccessDeniedException('Anonymous access denied');
        }
        $decklist = $this->decklistRepository->find($decklist_id);
        if (!$decklist) {
            throw $this->createNotFoundException('Decklist not found');
        }
        if (!$this->isGranted('ROLE_SUPER_ADMIN') && $user->getId() !== $decklist->getUser()->getId()) {
            throw $this->createAccessDeniedException('Access denied');
        }

        return $this->render('Decklist/decklist_edit.html.twig', ['url' => $this->generateUrl('decklist_save', ['decklist_id' => $decklist->getId()]), 'deck' => null, 'decklist' => $decklist]);
    }
}
