<?php

declare(strict_types=1);

namespace App\Model;

/**
 * The payload of a comment posted on a decklist, a fellowship or a quest log.
 */
final readonly class CommentDto
{
    public function __construct(
        public ?string $id = null,
        public string $comment = '',
    ) {
    }
}
