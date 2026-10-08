<?php

declare(strict_types=1);

namespace App\Model;

use Symfony\Component\Serializer\Attribute\SerializedName;

/**
 * The payload of the collection form: the owned packs, as "id" / "id:count" tokens.
 */
final readonly class SavePacksDto
{
    public function __construct(
        #[SerializedName('selected-packs')]
        public string $selectedPacks = '',
    ) {
    }
}
