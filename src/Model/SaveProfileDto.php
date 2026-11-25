<?php

declare(strict_types=1);

namespace App\Model;

use Symfony\Component\Serializer\Attribute\SerializedName;

/**
 * The payload of the user profile form. The flags are null when the value is not a boolean.
 */
final readonly class SaveProfileDto
{
    public function __construct(
        public ?string $username = null,
        public ?string $email = null,
        public ?string $resume = null,
        #[SerializedName('user_sphere_code')]
        public ?string $userSphereCode = null,
        #[SerializedName('notif_author')]
        public ?bool $notifAuthor = null,
        #[SerializedName('notif_commenter')]
        public ?bool $notifCommenter = null,
        #[SerializedName('notif_mention')]
        public ?bool $notifMention = null,
        #[SerializedName('share_decks')]
        public ?bool $shareDecks = null,
        #[SerializedName('dark_mode')]
        public ?bool $darkMode = null,
    ) {
    }
}
