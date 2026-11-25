<?php

declare(strict_types=1);

namespace App\Model;

use Symfony\Component\Serializer\Attribute\SerializedName;

/**
 * The query string of the fellowship search.
 */
final readonly class FellowshipSearchDto
{
    /**
     * @param array<mixed> $cards
     * @param array<mixed> $packs
     */
    public function __construct(
        public array $cards = [],
        public ?string $author = null,
        public ?string $name = null,
        #[SerializedName('nb_decks')]
        public ?string $nbDecks = null,
        public ?string $numcores = null,
        public ?string $numplaysets = null,
        public ?string $sort = null,
        public array $packs = [],
    ) {
    }
}
