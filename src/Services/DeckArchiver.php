<?php

namespace App\Services;

use App\Entity\User;
use App\Repository\DeckRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

class DeckArchiver
{
    public function __construct(private readonly string $cacheDir, private readonly DeckRepository $deckRepository, private readonly Environment $twig, private readonly Texts $texts)
    {
    }

    /**
     * @param array<int> $list_id
     *
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function downloadFromSelection(User $user, array $list_id, bool $octgn): Response
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

                if (!$deck->getUser()->isEqualTo($user)) {
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

        $contents = file_get_contents($file);
        if (false === $contents) {
            throw new \RuntimeException('Cannot read tmp file '.$file);
        }

        $response->setContent($contents);
        unlink($file);

        return $response;
    }
}
