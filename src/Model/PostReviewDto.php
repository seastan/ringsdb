<?php

declare(strict_types=1);

namespace App\Model;

use Symfony\Component\Serializer\Attribute\SerializedName;

/**
 * The payload of a new card review.
 */
final readonly class PostReviewDto
{
    public function __construct(
        #[SerializedName('card_id')]
        public ?string $cardId = null,
        public string $review = '',
    ) {
    }
}
