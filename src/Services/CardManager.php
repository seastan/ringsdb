<?php

declare(strict_types=1);

namespace App\Services;

use App\Entity\Card;
use App\Entity\CardPrinting;
use App\Repository\CardPrintingRepository;
use App\Repository\CardRepository;

class CardManager
{
    public function __construct(
        private readonly CardRepository $cardRepository,
        private readonly CardPrintingRepository $cardPrintingRepository,
    ) {
    }

    /**
     * The card with this code, or else the canonical card of the printing with this image code:
     * deck contents stored as JSON (quest log snapshots...) still use the codes of the cards
     * merged by the card-printings migration.
     */
    public function findCardByCode(string $code): ?Card
    {
        $card = $this->cardRepository->findOneBy(['code' => $code]);
        if ($card instanceof Card) {
            return $card;
        }

        $printing = $this->cardPrintingRepository->findOneBy(['imageCode' => $code]);

        return $printing instanceof CardPrinting ? $printing->getCard() : null;
    }

    /**
     * The cards with these codes, by code, like findCardByCode(), in two queries at most: the
     * cards, then the printings of the codes not found. The types and spheres come with the cards.
     *
     * @param list<string> $codes
     *
     * @return array<string, Card>
     */
    public function findCardsByCodes(array $codes): array
    {
        $codes = array_values(array_unique($codes));
        if ([] === $codes) {
            return [];
        }

        $cards = [];
        /** @var list<Card> $found */
        $found = $this->cardRepository->createQueryBuilder('c')
            ->addSelect('t', 's')
            ->leftJoin('c.type', 't')
            ->leftJoin('c.sphere', 's')
            ->where('c.code IN (:codes)')
            ->setParameter('codes', $codes)
            ->getQuery()
            ->getResult();
        foreach ($found as $card) {
            $cards[$card->getCode()] = $card;
        }

        $missing = array_values(array_diff($codes, array_map(strval(...), array_keys($cards))));
        if ([] === $missing) {
            return $cards;
        }

        /** @var list<CardPrinting> $printings */
        $printings = $this->cardPrintingRepository->createQueryBuilder('cp')
            ->addSelect('c', 't', 's')
            ->join('cp.card', 'c')
            ->leftJoin('c.type', 't')
            ->leftJoin('c.sphere', 's')
            ->where('cp.imageCode IN (:codes)')
            ->setParameter('codes', $missing)
            ->orderBy('cp.id')
            ->getQuery()
            ->getResult();
        foreach ($printings as $printing) {
            $card = $printing->getCard();
            if ($card instanceof Card) {
                $cards[$printing->getImageCode()] ??= $card;
            }
        }

        return $cards;
    }
}
