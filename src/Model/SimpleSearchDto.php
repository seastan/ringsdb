<?php

declare(strict_types=1);

namespace App\Model;

/**
 * The query string of the single card search input.
 */
final readonly class SimpleSearchDto
{
    public function __construct(
        public ?string $q = null,
        public ?string $page = null,
        public ?string $view = null,
        public ?string $sort = null,
    ) {
    }
}
