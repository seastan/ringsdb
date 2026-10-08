<?php

declare(strict_types=1);

namespace App\Controller\DeckBuilder;

use App\Controller\CurrentUserTrait;
use App\Services\DeckImporter;
use App\Services\DeckSaver;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\Routing\Attribute\Route;

class ImportAllController extends AbstractController
{
    use CurrentUserTrait;

    public function __construct(
        private DeckImporter $deckImporter,
        private DeckSaver $deckSaver
    ) {
    }

    #[Route(path: '/deck/import/all', name: 'decks_upload_all', methods: ['POST'])]
    public function __invoke(Request $request): RedirectResponse
    {
        $user = $this->currentUser();

        // time-consuming task
        ini_set('max_execution_time', '300');
        $uploadedFile = $request->files->get('uparchive');
        if (!isset($uploadedFile)) {
            throw new UnprocessableEntityHttpException('No file uploaded');
        }

        $filename = $uploadedFile->getPathname();
        if (function_exists('finfo_open')) {
            // return mime type ala mimetype extension
            $finfo = finfo_open(FILEINFO_MIME);
            $mime = false !== $finfo ? (string) finfo_file($finfo, $filename) : '';
            // check to see if the mime-type is 'zip'
            if (!str_starts_with($mime, 'application/zip')) {
                throw new UnprocessableEntityHttpException('Bad file');
            }
        }

        $zip = new \ZipArchive();
        $res = $zip->open($filename);
        if (true === $res) {
            for ($i = 0; $i < $zip->numFiles; ++$i) {
                $name = (string) $zip->getNameIndex($i);
                $data = $zip->getFromIndex($i);
                if (false === $data) {
                    throw new \RuntimeException('Cannot read from zip file '.$filename);
                }

                if ('o8d' === pathinfo($name, PATHINFO_EXTENSION)) {
                    $parse = $this->deckImporter->parseOctgnImport($data);
                } else {
                    $parse = $this->deckImporter->parseTextImport($data);
                }

                $deckname = pathinfo($name, PATHINFO_FILENAME);
                // one deck per file, even without any card (an empty deck)
                $this->deckSaver->save(
                    $user,
                    $parse['content'],
                    $deckname
                );
            }
        }

        $zip->close();
        $this->addFlash('notice', 'Decks imported.');

        return $this->redirect($this->generateUrl('decks_list'));
    }
}
