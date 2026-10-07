<?php

declare(strict_types=1);

namespace App\CardImageLocator;

use App\Entity\Card;
use App\Entity\CardPrinting;

interface CardImageLocatorInterface
{
    public function getCardImageUrl(Card $card): ?string;

    public function getPrintingImageUrl(CardPrinting $cardPrinting): ?string;
}
