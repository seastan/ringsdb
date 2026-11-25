<?php

declare(strict_types=1);

namespace App\Model;

use Symfony\Component\Serializer\Attribute\SerializedName;

/**
 * The payload of the fellowship publication form: for each of the (up to 4) private decks, the id
 * of the decklist to publish in its place (empty to publish the deck itself).
 */
final readonly class PublishFellowshipDto
{
    public function __construct(
        #[SerializedName('fellowship_id')]
        public ?string $fellowshipId = null,
        public ?string $name = null,
        public ?string $descriptionMd = null,
        #[SerializedName('deck_selection_1')]
        public ?string $deckSelection1 = null,
        #[SerializedName('deck_selection_2')]
        public ?string $deckSelection2 = null,
        #[SerializedName('deck_selection_3')]
        public ?string $deckSelection3 = null,
        #[SerializedName('deck_selection_4')]
        public ?string $deckSelection4 = null,
    ) {
    }

    /**
     * The decklist selected for the deck number $deckNumber (1 to 4).
     */
    public function deckSelection(int $deckNumber): ?string
    {
        return match ($deckNumber) {
            1 => $this->deckSelection1,
            2 => $this->deckSelection2,
            3 => $this->deckSelection3,
            4 => $this->deckSelection4,
            default => null,
        };
    }
}
