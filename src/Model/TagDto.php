<?php

declare(strict_types=1);

namespace App\Model;

/**
 * The payload of the deck tag actions: the ids of the decks and the tags to add or remove.
 */
final readonly class TagDto
{
    /**
     * @param array<mixed> $ids
     * @param array<mixed> $tags
     */
    public function __construct(
        public array $ids = [],
        public array $tags = [],
    ) {
    }
}
