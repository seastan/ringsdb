<?php

declare(strict_types=1);

namespace App\Controller\DeckBuilder;

use App\Repository\DeckRepository;
use App\Services\Texts;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

class OctgnDeckExportController extends AbstractController
{
    public function __construct(
        private readonly DeckRepository $deckRepository,
        private readonly Texts $texts
    ) {
    }

    /**
     * @Route(
     *     "/deck/export/octgn/{deck_id}",
     *     name="deck_export_octgn",
     *     methods={"GET"},
     *     requirements={"deck_id"="\d+"}
     * )
     */
    public function __invoke(int $deck_id): Response
    {
        /* @var $deck \App\Entity\Deck */
        $deck = $this->deckRepository->find($deck_id);
        if (!$deck) {
            throw new NotFoundHttpException("This deck doesn't exist.");
        }

        $is_owner = $this->getUser() && $this->getUser()->getId() == $deck->getUser()->getId();
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
