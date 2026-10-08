<?php

declare(strict_types=1);

namespace App\Model;

use Symfony\Component\Serializer\Attribute\SerializedName;

/**
 * The payload of the CSV upload: the code, old code and name of the pack the cards belong to.
 */
final readonly class CsvUploadDto
{
    public function __construct(
        public ?string $code = null,
        #[SerializedName('old_code')]
        public ?string $oldCode = null,
        public ?string $name = null,
    ) {
    }
}
