<?php

declare(strict_types=1);

namespace App\Model;

/**
 * The query string of the admin statistics: the month (Y-m) and, for the per-card stats, the step.
 */
final readonly class StatDto
{
    public function __construct(
        public ?string $month = null,
        public ?string $step = null,
    ) {
    }
}
