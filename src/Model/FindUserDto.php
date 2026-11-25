<?php

declare(strict_types=1);

namespace App\Model;

/**
 * The payload of the admin user search: a username, or else a user id.
 */
final readonly class FindUserDto
{
    public function __construct(
        public ?string $username = null,
        public ?string $id = null,
    ) {
    }
}
