<?php

declare(strict_types=1);

namespace App\Model;

use Symfony\Component\Serializer\Attribute\SerializedName;

/**
 * The payload of the preferred art of a card: the card code and the code of the pack of the printing.
 */
final readonly class SaveArtDto
{
    public function __construct(
        #[SerializedName('card_code')]
        public string $cardCode = '',
        #[SerializedName('pack_code')]
        public string $packCode = '',
    ) {
    }
}
