<?php

declare(strict_types=1);

namespace App\Controller\DeckBuilder;

use App\Services\DeckImporter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\Routing\Annotation\Route;

class FileImportDeckController extends AbstractController
{
    private DeckImporter $deckImporter;

    public function __construct(DeckImporter $deckImporter)
    {
        $this->deckImporter = $deckImporter;
    }

    /**
     * @Route("/deck/fileimport", name="deck_fileimport", methods={"POST"})
     */
    public function __invoke(Request $request): Response
    {
        $filetype = filter_var($request->get('type'), FILTER_SANITIZE_STRING);
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
            $is_text = 'text' == substr($mime, 0, 4) || 'application/xml' == substr($mime, 0, 15);
            if (!$is_text) {
                throw new UnprocessableEntityHttpException('Bad file');
            }
        }

        $contents = file_get_contents($filename);
        if (false === $contents) {
            throw new \RuntimeException('Cannot read from uploaded file '.$filename);
        }

        if ('octgn' == $filetype || 'auto' == $filetype && 'o8d' == $origext) {
            $parse = $this->deckImporter->parseOctgnImport($contents);
        } else {
            $parse = $this->deckImporter->parseTextImport($contents);
        }

        return $this->forward(SaveDeckController::class, ['name' => str_replace(".{$origext}", '', $origname), 'content' => json_encode($parse['content']), 'description' => $parse['description']]);
    }
}
