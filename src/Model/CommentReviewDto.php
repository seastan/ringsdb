<?php

declare(strict_types=1);

namespace App\Model;

use Symfony\Component\Serializer\Attribute\SerializedName;

/**
 * The payload of a comment posted on a card review.
 */
final readonly class CommentReviewDto
{
    public function __construct(
        #[SerializedName('comment_review_id')]
        public ?string $commentReviewId = null,
        public string $comment = '',
    ) {
    }
}
