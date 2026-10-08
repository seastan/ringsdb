<?php

declare(strict_types=1);

namespace App\Model;

use Symfony\Component\Serializer\Attribute\SerializedName;

/**
 * The query string of the user info endpoint: the objects to get the user's relation to, and the
 * optional JSONP callback.
 */
final readonly class UserInfoDto
{
    public function __construct(
        public ?string $jsonp = null,
        #[SerializedName('decklist_id')]
        public ?string $decklistId = null,
        #[SerializedName('fellowship_id')]
        public ?string $fellowshipId = null,
        #[SerializedName('questlog_id')]
        public ?string $questlogId = null,
        #[SerializedName('card_id')]
        public ?string $cardId = null,
    ) {
    }
}
