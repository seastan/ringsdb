<?php

declare(strict_types=1);

namespace App\Controller\DeckBuilder;

use App\Entity\Deck;
use App\Services\Texts;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Attribute\Route;

class OctgnDeckExportController extends AbstractController
{
    public function __construct(
        private readonly Texts $texts
    ) {
    }

    #[Route(path: '/deck/export/octgn/{deck_id}', name: 'deck_export_octgn', requirements: ['deck_id' => '\d+'], methods: ['GET'])]
    public function __invoke(#[MapEntity(id: 'deck_id', message: "This deck doesn't exist.")] Deck $deck): Response
    {
        $is_owner = $this->getUser() instanceof \Symfony\Component\Security\Core\User\UserInterface && $this->getUser()->getId() == $deck->getUser()->getId();
        if (!$deck->getUser()->getIsShareDecks() && !$is_owner) {
            throw new AccessDeniedHttpException('You are not allowed to view this deck. To get access, you can ask the deck owner to enable "Share my decks" on their account.');
        }

        $content = $this->renderView('Export/octgn.xml.twig', ['deck' => $deck->getTextExport()]);
        $response = new Response();
        $response->headers->set('Content-Type', 'application/octgn');
        $response->headers->set('Content-Disposition', $response->headers->makeDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $this->texts->slugify($deck->getName()).'.o8d'));
        $response->setContent($content);

        return $response;
    }
}
