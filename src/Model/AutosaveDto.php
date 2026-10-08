<?php

declare(strict_types=1);

namespace App\Model;

use Symfony\Component\Serializer\Attribute\SerializedName;

/**
 * The payload of the deckbuilder autosave: the deck and the JSON-encoded changes.
 */
final readonly class AutosaveDto
{
    public function __construct(
        #[SerializedName('deck_id')]
        public ?string $deckId = null,
        public string $diff = '',
    ) {
    }
}
