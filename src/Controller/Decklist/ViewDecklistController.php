<?php

declare(strict_types=1);

namespace App\Controller\Decklist;

use App\Entity\Decklist;
use App\Repository\DecklistRepository;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ViewDecklistController extends AbstractController
{
    public function __construct(
        private readonly int $cacheExpiration,
        private readonly DecklistRepository $decklistRepository
    ) {
    }

    /**
     * displays the content of a decklist along with comments, siblings, similar, etc..
     */
    #[Route(path: '/decklist/view/{decklist_id}/{decklist_name}', name: 'decklist_detail', requirements: ['decklist_id' => '\d+'], defaults: ['decklist_name' => null], methods: ['GET'])]
    public function __invoke(#[MapEntity(id: 'decklist_id', message: 'Decklist not found.')] Decklist $decklist): Response
    {
        $response = new Response();
        $response->setPublic();
        $response->setMaxAge($this->cacheExpiration);

        $duplicate = $this->decklistRepository->findOneBy(['signature' => $decklist->getSignature()]);
        if (!$duplicate || $duplicate->getDateCreation() >= $decklist->getDateCreation() || $duplicate->getId() === $decklist->getId()) {
            $duplicate = null;
        }

        $commenters = array_map(
            /* @var $comment \App\Entity\Comment */
            fn (\App\Entity\Comment $comment): string => $comment->getUser()->getUsername(),
            $decklist->getComments()->getValues()
        );
        $commenters[] = $decklist->getUser()->getUsername();
        $versions = $this->decklistRepository->findBy(['parent' => $decklist->getParent()], ['version' => 'DESC', 'id' => 'DESC']);

        return $this->render('Decklist/decklist.html.twig', ['pagetitle' => $decklist->getName(), 'decklist' => $decklist, 'duplicate' => $duplicate, 'commenters' => $commenters, 'versions' => $versions], $response);
    }
}
