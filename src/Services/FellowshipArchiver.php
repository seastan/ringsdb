<?php

namespace App\Services;

use App\Entity\Deck;
use App\Entity\User;
use App\Repository\FellowshipRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Twig\Environment;

class FellowshipArchiver
{
    public function __construct(private string $cacheDir, private FellowshipRepository $fellowshipRepository, private Environment $twig, private Texts $texts)
    {
    }

    public function downloadFromSelection(User $user, int $fellowship_id, bool $octgn): Response
    {
        /* @var $fellowship \App\Entity\Fellowship */
        $fellowship = $this->fellowshipRepository->find($fellowship_id);
        if (!$fellowship) {
            throw new AccessDeniedHttpException("You don't have access to this fellowship.");
        }

        $fellowship_user = $fellowship->getUser();
        $is_public = $fellowship->getIsPublic();
        if (!$fellowship_user->isEqualTo($user) && !$fellowship_user->getIsShareDecks() && !$is_public) {
            throw new AccessDeniedHttpException("You don't have access to this fellowship.");
        }

        $tmpDir = $this->cacheDir;
        $file = tempnam($tmpDir, 'zip');
        if (false === $file) {
            throw new \RuntimeException("Cannot create a temporary file in {$tmpDir}");
        }

        $zip = new \ZipArchive();
        $res = $zip->open($file, \ZipArchive::OVERWRITE);
        if (true === $res) {
            $decks = [];
            /* @var $fellowship_decks \App\Entity\FellowshipDeck[] */
            $fellowship_decks = $fellowship->getDecks();
            foreach ($fellowship_decks as $fellowship_deck) {
                $decks[] = $fellowship_deck->getDeck();
            }

            /* @var $fellowship_decks \App\Entity\FellowshipDecklist[] */
            $fellowship_decklists = $fellowship->getDecklists();
            foreach ($fellowship_decklists as $fellowship_decklist) {
                $decks[] = $fellowship_decklist->getDecklist();
            }

            foreach ($decks as $deck) {
                /* @var $deck Deck */
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
        $response->headers->set('Content-Disposition', $response->headers->makeDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $this->texts->slugify('RingsDB - Fellowship '.$fellowship_id).'.zip'));

        $contents = file_get_contents($file);
        if (false === $contents) {
            throw new \RuntimeException('Cannot read tmp file '.$file);
        }

        $response->setContent($contents);
        unlink($file);

        return $response;
    }
}
