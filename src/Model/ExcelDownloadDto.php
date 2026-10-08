<?php

declare(strict_types=1);

namespace App\Model;

/**
 * The payload of the Excel download: the id of the pack to export, 0 for all the cards.
 */
final readonly class ExcelDownloadDto
{
    public function __construct(
        public ?string $pack = null,
    ) {
    }
}
