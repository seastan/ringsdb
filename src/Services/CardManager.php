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
}
