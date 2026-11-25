<?php

declare(strict_types=1);

namespace App\Model;

/**
 * The payload of a bulk deletion: the ids of the objects, separated by dashes.
 */
final readonly class DeleteListDto
{
    public function __construct(
        public string $ids = '',
    ) {
    }
}
