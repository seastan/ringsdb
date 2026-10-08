<?php

declare(strict_types=1);

namespace App\Model;

use Symfony\Component\Serializer\Attribute\SerializedName;

/**
 * The query string of the quest log search.
 */
final readonly class QuestlogSearchDto
{
    /**
     * @param array<mixed> $cards
     * @param array<mixed> $packs
     */
    public function __construct(
        public array $cards = [],
        public ?string $author = null,
        public ?string $name = null,
        public ?string $scenario = null,
        #[SerializedName('nb_decks')]
        public ?string $nbDecks = null,
        public ?string $sort = null,
        public array $packs = [],
    ) {
    }
}
