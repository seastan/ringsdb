<?php

declare(strict_types=1);

namespace App\Model;

use Symfony\Component\Serializer\Attribute\SerializedName;

/**
 * The payload of the edition of a card review.
 */
final readonly class EditReviewDto
{
    public function __construct(
        #[SerializedName('review_id')]
        public ?string $reviewId = null,
        public string $review = '',
    ) {
    }
}
