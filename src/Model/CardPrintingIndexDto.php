<?php

declare(strict_types=1);

namespace App\Model;

/**
 * The query string of the card printing admin list: the pack id and the card name filters.
 */
final readonly class CardPrintingIndexDto
{
    public function __construct(
        public ?string $pack = null,
        public ?string $card = null,
    ) {
    }
}
