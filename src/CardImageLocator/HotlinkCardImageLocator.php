<?php

declare(strict_types=1);

namespace App\CardImageLocator;

use App\Entity\Card;
use App\Entity\CardPrinting;

class HotlinkCardImageLocator implements CardImageLocatorInterface
{
    private function getImageUrlByCode(string $code): string
    {
        return sprintf('https://ringsdb.com/bundles/cards/%s.png', $code);
    }

    public function getCardImageUrl(Card $card): ?string
    {
        return $this->getImageUrlByCode($card->getCode());
    }

    public function getPrintingImageUrl(CardPrinting $cardPrinting): ?string
    {
        return $this->getImageUrlByCode($cardPrinting->getImageCode());
    }
}
