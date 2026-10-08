<?php

declare(strict_types=1);

namespace App\Controller\DeckBuilder;

use App\Controller\CurrentUserTrait;
use App\Helper\StringSanitizer;
use App\Services\DeckImporter;
use App\Services\DeckSaver;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\Routing\Attribute\Route;

class FileImportDeckController extends AbstractController
{
    use CurrentUserTrait;

    public function __construct(
        private readonly DeckImporter $deckImporter,
        private readonly DeckSaver $deckSaver
    ) {
    }

    #[Route(path: '/deck/fileimport', name: 'deck_fileimport', methods: ['POST'])]
    public function __invoke(Request $request): Response
    {
        $filetype = StringSanitizer::sanitize($request->request->get('type'));
        $uploadedFile = $request->files->get('upfile');
        if (!isset($uploadedFile)) {
            throw new UnprocessableEntityHttpException('No file uploaded');
        }

        $origname = $uploadedFile->getClientOriginalName();
        $origext = $uploadedFile->getClientOriginalExtension();
        $filename = $uploadedFile->getPathname();
        if (function_exists('finfo_open')) {
            // return mime type ala mimetype extension
            $finfo = finfo_open(FILEINFO_MIME);
            $mime = false !== $finfo ? (string) finfo_file($finfo, $filename) : '';
            // check to see if the mime-type starts with 'text'
            $is_text = str_starts_with($mime, 'text') || str_starts_with($mime, 'application/xml');
            if (!$is_text) {
                throw new UnprocessableEntityHttpException('Bad file');
            }
        }

        $contents = file_get_contents($filename);
        if (false === $contents) {
            throw new \RuntimeException('Cannot read from uploaded file '.$filename);
        }

        if ('octgn' === $filetype || 'auto' === $filetype && 'o8d' == $origext) {
            $parse = $this->deckImporter->parseOctgnImport($contents);
        } else {
            $parse = $this->deckImporter->parseTextImport($contents);
        }

        $this->deckSaver->save($this->currentUser(), null, null, $parse['content'], str_replace(".{$origext}", '', $origname), $parse['description']);

        return $this->redirectToRoute('decks_list');
    }
}
