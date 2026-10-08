<?php

declare(strict_types=1);

namespace App\Model;

/**
 * The payload of the admin command runner.
 */
final readonly class CommandRunDto
{
    public function __construct(
        public ?string $command = null,
        public ?string $scenario = null,
        public ?string $customjson = null,
    ) {
    }
}
