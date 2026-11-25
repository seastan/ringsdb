<?php

declare(strict_types=1);

namespace App\Model;

use Symfony\Component\Serializer\Attribute\SerializedName;

/**
 * The payload of the custom pack form: its name and its cards, as JSON.
 */
final readonly class CustomPackDto
{
    public function __construct(
        public string $name = '',
        #[SerializedName('cards_json')]
        public string $cardsJson = '[]',
    ) {
    }
}
