<?php

declare(strict_types=1);

namespace App\Model;

use Symfony\Component\Serializer\Attribute\SerializedName;

/**
 * The query string of the card printing admin pages: the pack the pages are filtered on.
 */
final readonly class FilterPackDto
{
    public function __construct(
        #[SerializedName('filter_pack')]
        public ?string $filterPack = null,
    ) {
    }
}
