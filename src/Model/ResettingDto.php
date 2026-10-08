<?php

declare(strict_types=1);

namespace App\Model;

/**
 * The username (or email) of the password resetting pages.
 */
final readonly class ResettingDto
{
    public function __construct(
        public ?string $username = null,
    ) {
    }
}
