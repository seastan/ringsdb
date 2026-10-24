<?php

declare(strict_types=1);

namespace App\Controller\Decklist;

use App\Entity\Decklist;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Attribute\Route;

class TextExportDecklistController extends AbstractController
{
    public function __construct(
        private readonly int $cacheExpiration
    ) {
    }

    /**
     * returns a text file with the content of a decklist.
     */
    #[Route(path: '/decklist/export/text/{decklist_id}', name: 'decklist_export_text', requirements: ['decklist_id' => '\d+'], methods: ['GET'])]
    public function __invoke(#[MapEntity(id: 'decklist_id', message: 'Unable to find decklist.')] Decklist $decklist): Response
    {
        $response = new Response();
        $response->setPublic();
        $response->setMaxAge($this->cacheExpiration);

        $content = $this->renderView('Export/plain.txt.twig', ['deck' => $decklist->getTextExport()]);
        $content = str_replace("\n", "\r\n", $content);

        $response = new Response();
        $response->headers->set('Content-Type', 'text/plain');
        $response->headers->set('Content-Disposition', $response->headers->makeDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $decklist->getNameCanonical().'.txt'));
        $response->setContent($content);

        return $response;
    }
}
