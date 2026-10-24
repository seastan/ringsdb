<?php

declare(strict_types=1);

namespace App\Model;

use Symfony\Component\Serializer\Attribute\SerializedName;

/**
 * The query string of the decklist search results, used to fill the search form again.
 */
final readonly class DecklistSearchDto
{
    /**
     * @param array<mixed> $cards
     * @param array<mixed> $cardsToExclude
     * @param array<mixed> $packs
     */
    public function __construct(
        public array $cards = [],
        #[SerializedName('cards_to_exclude')]
        public array $cardsToExclude = [],
        public ?string $sphere = null,
        public ?string $author = null,
        public ?string $name = null,
        public ?string $threat = null,
        public ?string $threato = null,
        public ?string $reputation = null,
        public ?string $reputationo = null,
        public ?string $numcores = null,
        #[SerializedName('require_description')]
        public ?string $requireDescription = null,
        public ?string $sort = null,
        public array $packs = [],
    ) {
    }
}
