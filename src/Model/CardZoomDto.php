<?php

declare(strict_types=1);

namespace App\Model;

/**
 * The query string of the card page: the code of the pack whose printing is shown.
 */
final readonly class CardZoomDto
{
    public function __construct(
        public ?string $pack = null,
    ) {
    }
}
