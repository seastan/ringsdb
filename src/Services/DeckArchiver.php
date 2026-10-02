<?php

namespace App\Services;

use App\Entity\User;
use App\Repository\DeckRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Twig\Environment;

class DeckArchiver
{
    private string $cacheDir;
    private DeckRepository $deckRepository;
    private Environment $twig;
    private Texts $texts;

    public function __construct(
        string $cacheDir,
        DeckRepository $deckRepository,
        Environment $twig,
        Texts $texts
    ) {
        $this->cacheDir = $cacheDir;
        $this->deckRepository = $deckRepository;
        $this->twig = $twig;
        $this->texts = $texts;
    }

    public function downloadFromSelection(User $user, $list_id, $octgn): Response
    {
        $tmpDir = $this->cacheDir;
        $file = tempnam($tmpDir, 'zip');
        if (false === $file) {
            throw new \RuntimeException("Cannot create a temporary file in {$tmpDir}");
        }
        $zip = new \ZipArchive();
        $res = $zip->open($file, \ZipArchive::OVERWRITE);
        if (true === $res) {
            foreach ($list_id as $id) {
                /* @var $deck \App\Entity\Deck */
                $deck = $this->deckRepository->find($id);
                if (!$deck) {
                    continue;
                }
                if ($user->getId() != $deck->getUser()->getId()) {
                    continue;
                }
                if ($octgn) {
                    $extension = 'o8d';
                    $content = $this->twig->render('Export/octgn.xml.twig', ['deck' => $deck->getTextExport()]);
                } else {
                    $extension = 'txt';
                    $content = $this->twig->render('Export/plain.txt.twig', ['deck' => $deck->getTextExport()]);
                }
                $filename = $this->texts->slugify($deck->getName()).' '.$deck->getVersion().'.'.$extension;
                $zip->addFromString($filename, $content);
            }
            $zip->close();
        }
        $response = new Response();
        $response->headers->set('Content-Type', 'application/zip');
        $response->headers->set('Content-Length', (string) filesize($file));
        $response->headers->set('Content-Disposition', $response->headers->makeDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $this->texts->slugify('ringsdb').'.zip'));
        $response->setContent(file_get_contents($file));
        unlink($file);

        return $response;
    }
}
