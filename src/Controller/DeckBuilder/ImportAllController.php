<?php

declare(strict_types=1);

namespace App\Controller\DeckBuilder;

use App\Entity\Deck;
use App\Services\DeckImporter;
use App\Services\Decks;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\Routing\Annotation\Route;

class ImportAllController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private DeckImporter $deckImporter;
    private Decks $decks;

    public function __construct(
        EntityManagerInterface $entityManager,
        DeckImporter $deckImporter,
        Decks $decks
    ) {
        $this->entityManager = $entityManager;
        $this->deckImporter = $deckImporter;
        $this->decks = $decks;
    }

    /**
     * @Route("/deck/import/all", name="decks_upload_all", methods={"POST"})
     */
    public function __invoke(Request $request): RedirectResponse
    {
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
            if ('application/zip' !== substr($mime, 0, 15)) {
                throw new UnprocessableEntityHttpException('Bad file');
            }
        }
        $zip = new \ZipArchive();
        $res = $zip->open($filename);
        if (true === $res) {
            for ($i = 0; $i < $zip->numFiles; ++$i) {
                $name = (string) $zip->getNameIndex($i);
                if ('o8d' == pathinfo($name, PATHINFO_EXTENSION)) {
                    $parse = $this->deckImporter->parseOctgnImport($zip->getFromIndex($i));
                } else {
                    $parse = $this->deckImporter->parseTextImport($zip->getFromIndex($i));
                }
                $deckname = pathinfo($name, PATHINFO_FILENAME);
                // one deck per file, even without any card (an empty deck)
                /* @var $deck \App\Entity\Deck */
                $deck = new Deck();
                $this->entityManager->persist($deck);
                $this->decks->saveDeck($this->getUser(), $deck, null, $deckname, '', '', $parse['content'], null);
            }
        }
        $zip->close();
        $this->entityManager->flush();
        $this->get('session')->getFlashBag()->set('notice', 'Decks imported.');

        return $this->redirect($this->generateUrl('decks_list'));
    }
}
