<?php

declare(strict_types=1);

namespace App\Model;

/**
 * The query string of the export of a selection of decks: the ids of the decks.
 */
final readonly class DeckIdsDto
{
    /**
     * @param array<mixed> $ids
     */
    public function __construct(
        public array $ids = [],
    ) {
    }
}
