<?php

declare(strict_types=1);

namespace App\Model;

use Symfony\Component\Serializer\Attribute\SerializedName;

/**
 * The payload of the deletion of a quest log.
 */
final readonly class DeleteQuestlogDto
{
    public function __construct(
        #[SerializedName('questlog_id')]
        public ?string $questlogId = null,
    ) {
    }
}
