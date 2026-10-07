<?php

declare(strict_types=1);

namespace App\CardImageLocator;

use App\Entity\Card;
use App\Entity\CardPrinting;

class NoopCardImageLocator implements CardImageLocatorInterface
{
    public function getCardImageUrl(Card $card): ?string
    {
        return null;
    }

    public function getPrintingImageUrl(CardPrinting $cardPrinting): ?string
    {
        return null;
    }
}
