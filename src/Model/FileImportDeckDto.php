<?php

declare(strict_types=1);

namespace App\Model;

/**
 * The payload of the deck file import, besides the file: its type (octgn, text or auto).
 */
final readonly class FileImportDeckDto
{
    public function __construct(
        public ?string $type = null,
    ) {
    }
}
