<?php

declare(strict_types=1);

namespace App\Model;

/**
 * The payload of the Excel upload: the "create" checkbox, which enables card creation when present.
 */
final readonly class ExcelUploadDto
{
    public function __construct(
        public ?string $create = null,
    ) {
    }
}
