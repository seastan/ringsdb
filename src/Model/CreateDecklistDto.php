<?php

declare(strict_types=1);

namespace App\Model;

use Symfony\Component\Serializer\Attribute\SerializedName;

/**
 * The payload of the decklist publication form: the deck to publish and the decklist details.
 */
final readonly class CreateDecklistDto
{
    public function __construct(
        #[SerializedName('deck_id')]
        public ?string $deckId = null,
        public ?string $name = null,
        public ?string $descriptionMd = null,
        public ?string $precedent = null,
    ) {
    }
}
