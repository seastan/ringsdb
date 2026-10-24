<?php

declare(strict_types=1);

namespace App\Model;

use Symfony\Component\Serializer\Attribute\SerializedName;

/**
 * A payload that designates one deck of the deckbuilder by its id.
 */
final readonly class DeckIdDto
{
    public function __construct(
        #[SerializedName('deck_id')]
        public ?string $deckId = null,
    ) {
    }
}
