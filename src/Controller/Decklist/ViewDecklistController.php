<?php

declare(strict_types=1);

namespace App\Controller\Decklist;

use App\Entity\Decklist;
use App\Repository\DecklistRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ViewDecklistController extends AbstractController
{
    private int $cacheExpiration;
    private DecklistRepository $decklistRepository;

    public function __construct(
        int $cacheExpiration,
        DecklistRepository $decklistRepository
    ) {
        $this->cacheExpiration = $cacheExpiration;
        $this->decklistRepository = $decklistRepository;
    }

    /**
     * displays the content of a decklist along with comments, siblings, similar, etc..
     *
     * @Route(
     *     "/decklist/view/{decklist_id}/{decklist_name}",
     *     name="decklist_detail",
     *     methods={"GET"},
     *     requirements={"decklist_id"="\d+"},
     *     defaults={"decklist_name"=null}
     * )
     */
    public function __invoke($decklist_id): Response
    {
        $response = new Response();
        $response->setPublic();
        $response->setMaxAge($this->cacheExpiration);
        $decklist = $this->decklistRepository->find($decklist_id);
        if (!$decklist) {
            throw $this->createNotFoundException('Decklist not found.');
        }
        $duplicate = $this->decklistRepository->findOneBy(['signature' => $decklist->getSignature()]);
        if (!$duplicate || $duplicate->getDateCreation() >= $decklist->getDateCreation() || $duplicate->getId() === $decklist->getId()) {
            $duplicate = null;
        }
        $commenters = array_map(function ($comment) {
            /* @var $comment \App\Entity\Comment */
            return $comment->getUser()->getUsername();
        }, $decklist->getComments()->getValues());
        $commenters[] = $decklist->getUser()->getUsername();
        $versions = $this->decklistRepository->findBy(['parent' => $decklist->getParent()], ['version' => 'DESC', 'id' => 'DESC']);

        return $this->render('Decklist/decklist.html.twig', ['pagetitle' => $decklist->getName(), 'decklist' => $decklist, 'duplicate' => $duplicate, 'commenters' => $commenters, 'versions' => $versions], $response);
    }
}
