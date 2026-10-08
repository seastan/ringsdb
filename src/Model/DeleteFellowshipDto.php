<?php

declare(strict_types=1);

namespace App\Model;

use Symfony\Component\Serializer\Attribute\SerializedName;

/**
 * The payload of the deletion of a fellowship.
 */
final readonly class DeleteFellowshipDto
{
    public function __construct(
        #[SerializedName('fellowship_id')]
        public ?string $fellowshipId = null,
    ) {
    }
}
