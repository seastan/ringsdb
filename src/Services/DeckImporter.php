<?php

namespace App\Services;

use App\Entity\Card;
use App\Entity\Pack;
use App\Repository\CardRepository;
use App\Repository\PackRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DomCrawler\Crawler;

class DeckImporter
{
    public function __construct(private EntityManagerInterface $entityManager, private CardRepository $cardRepository, private PackRepository $packRepository)
    {
    }

    /**
     * @return array{content: array{main: array<int|string, int>, side: array<int|string, int>}, description: string}
     */
    public function parseTextImport(string $text): array
    {
        $content = ['main' => [], 'side' => []];
        $addToSideboard = false;
        $text = str_replace(['“', '”', '’', '&rsquo;'], ['"', '"', "'", "'"], $text);
        $lines = explode("\n", $text);
        foreach ($lines as $line) {
            $matches = [];
            $pack_name = null;
            $name = null;
            $quantity = 1;
            if ('Sideboard' === trim($line)) {
                $addToSideboard = true;
                continue;
            }

            if (preg_match('/(x\\d+|\\d+x)/u', $line, $matches)) {
                $quantity = intval(str_replace('x', '', $matches[1]));
                $line = str_replace($matches[1], '', $line);
            }

            if (preg_match('/^\\s*([\\pLl\\pLu\\pN\\-\\.\'\\!\\: ]+)\\(?([^\\)]*)\\)?/u', $line, $matches)) {
                $name = trim($matches[1]);
                // the pack name, empty when absent
                $pack_name = trim($matches[2]);
            }

            $card = null;
            $pack = null;
            if ($pack_name) {
                /* @var $pack Pack */
                $pack = $this->packRepository->findOneBy(['name' => $pack_name]);
                if (!$pack) {
                    $pack = $this->packRepository->findOneBy(['code' => $pack_name]);
                }
            }

            if ($pack) {
                // a card belongs to its packs through its printings
                /* @var $card \App\Entity\Card */
                $card = $this->entityManager->createQuery('SELECT c FROM App:Card c JOIN c.printings p WHERE c.name = :name AND p.pack = :pack ORDER BY c.code')->setParameter('name', $name)->setParameter('pack', $pack)->setMaxResults(1)->getOneOrNullResult();
            } else {
                /* @var $pack \App\Entity\Card */
                $card = $this->cardRepository->findOneBy(['name' => $name]);
            }

            if ($card) {
                if ($addToSideboard) {
                    $content['side'][$card->getCode()] = $quantity;
                } else {
                    $content['main'][$card->getCode()] = $quantity;
                }
            }
        }

        return ['content' => $content, 'description' => ''];
    }

    /**
     * @return array{content: array{main: array<int|string, int>, side: array<int|string, int>}, description: string}
     */
    public function parseOctgnImport(string $octgn): array
    {
        $crawler = new Crawler();
        $crawler->addXmlContent($octgn);
        // read octgnid
        $octgnids = [];
        $sideoctgnids = [];
        $cardcrawler = $crawler->filter('deck > section[name!="Sideboard"] > card');
        /** @var \DOMElement $domElement */
        foreach ($cardcrawler as $domElement) {
            $octgnids[$domElement->getAttribute('id')] = intval($domElement->getAttribute('qty'));
        }

        $cardcrawler = $crawler->filter('deck > section[name="Sideboard"] > card');
        /** @var \DOMElement $domElement */
        foreach ($cardcrawler as $domElement) {
            $sideoctgnids[$domElement->getAttribute('id')] = intval($domElement->getAttribute('qty'));
        }

        // read desc
        $desccrawler = $crawler->filter('deck > notes');
        $descriptions = [];
        /** @var \DOMElement $domElement */
        foreach ($desccrawler as $domElement) {
            $descriptions[] = $domElement->nodeValue;
        }

        $content = [];
        foreach ($octgnids as $octgnid => $qty) {
            $card = $this->findCardByOctgnid($octgnid);
            if ($card instanceof Card) {
                // several printings of a card can have their own octgnid
                $content[$card->getCode()] = ($content[$card->getCode()] ?? 0) + $qty;
            }
        }

        $sidecontent = [];
        foreach ($sideoctgnids as $octgnid => $qty) {
            $card = $this->findCardByOctgnid($octgnid);
            if ($card instanceof Card) {
                $sidecontent[$card->getCode()] = ($sidecontent[$card->getCode()] ?? 0) + $qty;
            }
        }

        $description = implode("\n", $descriptions);

        return ['content' => ['main' => $content, 'side' => $sidecontent], 'description' => $description];
    }

    /**
     * The card of a printing, by its octgnid. The Messenger of the King version of a hero has the
     * octgnid of the hero: the original card (the lowest id) is chosen, as before the printings
     * refactor.
     *
     * @param string $octgnid
     */
    private function findCardByOctgnid(int|string $octgnid): ?Card
    {
        $printing = $this->entityManager->createQueryBuilder()->select('cp')->from('App:CardPrinting', 'cp')->join('cp.card', 'c')->where('cp.octgnid = :octgnid')->setParameter('octgnid', $octgnid)->orderBy('c.id', 'ASC')->setMaxResults(1)->getQuery()->getOneOrNullResult();

        return $printing ? $printing->getCard() : null;
    }
}
