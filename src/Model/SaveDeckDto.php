<?php

declare(strict_types=1);

namespace App\Model;

use Symfony\Component\Serializer\Attribute\SerializedName;

/**
 * The payload of the deckbuilder save, as a form or through AJAX: the deck (none for a new one), its
 * JSON-encoded content and its details. cancel_edits and copy are only sent by the form.
 */
final readonly class SaveDeckDto
{
    public function __construct(
        public ?string $id = null,
        public string $content = '',
        public string $name = '',
        public string $description = '',
        public string $tags = '',
        #[SerializedName('cancel_edits')]
        public ?string $cancelEdits = null,
        public ?string $copy = null,
    ) {
    }
}
