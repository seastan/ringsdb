<?php

declare(strict_types=1);

namespace App\Model;

/**
 * The payload of the decklist edition form.
 */
final readonly class SaveDecklistDto
{
    public function __construct(
        public ?string $name = null,
        public ?string $descriptionMd = null,
        public ?string $precedent = null,
    ) {
    }
}
