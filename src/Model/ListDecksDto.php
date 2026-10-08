<?php

declare(strict_types=1);

namespace App\Model;

/**
 * The query string of the deck list: whether all the decks are shown, any non-empty value but "0".
 */
final readonly class ListDecksDto
{
    public function __construct(
        public ?string $all = null,
    ) {
    }
}
