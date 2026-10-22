<?php

declare(strict_types=1);

namespace App\Controller\Decklist;

use App\Entity\Decklist;
use App\Repository\DecklistRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

class TextExportDecklistController extends AbstractController
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
     * returns a text file with the content of a decklist.
     *
     * @Route(
     *     "/decklist/export/text/{decklist_id}",
     *     name="decklist_export_text",
     *     methods={"GET"},
     *     requirements={"decklist_id"="\d+"}
     * )
     */
    public function __invoke(int $decklist_id): Response
    {
        $response = new Response();
        $response->setPublic();
        $response->setMaxAge($this->cacheExpiration);

        /* @var $decklist \App\Entity\Decklist */
        $decklist = $this->decklistRepository->find($decklist_id);
        if (!$decklist) {
            throw new NotFoundHttpException('Unable to find decklist.');
        }

        $content = $this->renderView('Export/plain.txt.twig', ['deck' => $decklist->getTextExport()]);
        $content = str_replace("\n", "\r\n", $content);

        $response = new Response();
        $response->headers->set('Content-Type', 'text/plain');
        $response->headers->set('Content-Disposition', $response->headers->makeDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $decklist->getNameCanonical().'.txt'));
        $response->setContent($content);

        return $response;
    }
}
