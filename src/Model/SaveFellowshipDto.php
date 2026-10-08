<?php

declare(strict_types=1);

namespace App\Model;

use Symfony\Component\Serializer\Attribute\SerializedName;

/**
 * The payload of the fellowship editor: the fellowship and its (up to 4) decks or decklists.
 */
final readonly class SaveFellowshipDto
{
    public function __construct(
        #[SerializedName('fellowship_id')]
        public ?string $fellowshipId = null,
        public ?string $name = null,
        #[SerializedName('auto_publish')]
        public ?string $autoPublish = null,
        public ?string $descriptionMd = null,
        #[SerializedName('deck1_id')]
        public ?string $deck1Id = null,
        #[SerializedName('deck1_is_decklist')]
        public ?string $deck1IsDecklist = null,
        #[SerializedName('deck2_id')]
        public ?string $deck2Id = null,
        #[SerializedName('deck2_is_decklist')]
        public ?string $deck2IsDecklist = null,
        #[SerializedName('deck3_id')]
        public ?string $deck3Id = null,
        #[SerializedName('deck3_is_decklist')]
        public ?string $deck3IsDecklist = null,
        #[SerializedName('deck4_id')]
        public ?string $deck4Id = null,
        #[SerializedName('deck4_is_decklist')]
        public ?string $deck4IsDecklist = null,
    ) {
    }

    /**
     * The id of the deck (or decklist) number $i (1 to 4).
     */
    public function deckId(int $i): ?string
    {
        return match ($i) {
            1 => $this->deck1Id,
            2 => $this->deck2Id,
            3 => $this->deck3Id,
            4 => $this->deck4Id,
            default => null,
        };
    }

    /**
     * Whether the deck number $i (1 to 4) is a decklist ("true") or a deck.
     */
    public function deckIsDecklist(int $i): ?string
    {
        return match ($i) {
            1 => $this->deck1IsDecklist,
            2 => $this->deck2IsDecklist,
            3 => $this->deck3IsDecklist,
            4 => $this->deck4IsDecklist,
            default => null,
        };
    }
}
