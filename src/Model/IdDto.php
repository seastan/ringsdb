<?php

declare(strict_types=1);

namespace App\Model;

/**
 * A payload that designates one object by its id (vote, favorite, like…).
 */
final readonly class IdDto
{
    public function __construct(
        public ?string $id = null,
    ) {
    }
}
