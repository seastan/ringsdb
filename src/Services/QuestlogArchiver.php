<?php

namespace App\Services;

use App\Entity\Deck;
use App\Entity\User;
use App\Repository\QuestlogRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Twig\Environment;

class QuestlogArchiver
{
    private QuestlogRepository $questlogRepository;

    private string $cacheDir;

    private Decks $decks;

    private Environment $twig;

    private Texts $texts;

    public function __construct(
        string $cacheDir,
        QuestlogRepository $questlogRepository,
        Decks $decks,
        Environment $twig,
        Texts $texts
    ) {
        $this->questlogRepository = $questlogRepository;
        $this->cacheDir = $cacheDir;
        $this->decks = $decks;
        $this->twig = $twig;
        $this->texts = $texts;
    }

    public function downloadFromSelection(User $user, int $questlog_id, bool $octgn): Response
    {
        /* @var $questlog \App\Entity\QuestLog */
        $questlog = $this->questlogRepository->find($questlog_id);
        if (!$questlog) {
            throw new AccessDeniedHttpException("You don't have access to this questlog.");
        }

        $questlog_user = $questlog->getUser();
        $is_public = $questlog->getIsPublic();
        if (!$questlog_user->isEqualTo($user)
            && !$questlog_user->getIsShareDecks()
            && !$is_public) {
            throw new AccessDeniedHttpException("You don't have access to this questlog.");
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
            /* @var $questlog_decks \App\Entity\QuestlogDeck[] */
            $questlog_decks = $questlog->getDecks();
            foreach ($questlog_decks as $questlog_deck) {
                $deck = $questlog_deck->getDeck();
                if ($deck instanceof Deck) {
                    $this->decks->setSlots($deck, json_decode($questlog_deck->getContent(), true));
                    $decks[] = $deck;
                }
            }

            foreach ($decks as $deck) {
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
        $response->headers->set('Content-Disposition', $response->headers->makeDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $this->texts->slugify('RingsDB - Quest Log '.$questlog_id).'.zip'));

        $contents = file_get_contents($file);
        if (false === $contents) {
            throw new \RuntimeException('Cannot read tmp file '.$file);
        }

        $response->setContent($contents);
        unlink($file);

        return $response;
    }
}
