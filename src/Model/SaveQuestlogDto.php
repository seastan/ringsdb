<?php

declare(strict_types=1);

namespace App\Model;

use Symfony\Component\Serializer\Attribute\SerializedName;

/**
 * The payload of the quest log editor, with its NB_DECKS deck slots.
 */
final readonly class SaveQuestlogDto
{
    public const int NB_DECKS = 4;

    public function __construct(
        #[SerializedName('questlog_id')]
        public ?string $questlogId = null,
        public ?string $name = null,
        public ?string $descriptionMd = null,
        public ?string $quest = null,
        public ?string $date = null,
        public ?string $difficulty = null,
        public ?string $victory = null,
        public ?string $score = null,
        public ?string $public = null,
        #[SerializedName('deck1_id')]
        public ?string $deck1Id = null,
        #[SerializedName('deck1_is_decklist')]
        public ?string $deck1IsDecklist = null,
        #[SerializedName('questlogdeck1_player_name')]
        public ?string $questlogdeck1PlayerName = null,
        #[SerializedName('questlogdeck1_content')]
        public string $questlogdeck1Content = '',
        #[SerializedName('deck1_content')]
        public string $deck1Content = '',
        #[SerializedName('deck2_id')]
        public ?string $deck2Id = null,
        #[SerializedName('deck2_is_decklist')]
        public ?string $deck2IsDecklist = null,
        #[SerializedName('questlogdeck2_player_name')]
        public ?string $questlogdeck2PlayerName = null,
        #[SerializedName('questlogdeck2_content')]
        public string $questlogdeck2Content = '',
        #[SerializedName('deck2_content')]
        public string $deck2Content = '',
        #[SerializedName('deck3_id')]
        public ?string $deck3Id = null,
        #[SerializedName('deck3_is_decklist')]
        public ?string $deck3IsDecklist = null,
        #[SerializedName('questlogdeck3_player_name')]
        public ?string $questlogdeck3PlayerName = null,
        #[SerializedName('questlogdeck3_content')]
        public string $questlogdeck3Content = '',
        #[SerializedName('deck3_content')]
        public string $deck3Content = '',
        #[SerializedName('deck4_id')]
        public ?string $deck4Id = null,
        #[SerializedName('deck4_is_decklist')]
        public ?string $deck4IsDecklist = null,
        #[SerializedName('questlogdeck4_player_name')]
        public ?string $questlogdeck4PlayerName = null,
        #[SerializedName('questlogdeck4_content')]
        public string $questlogdeck4Content = '',
        #[SerializedName('deck4_content')]
        public string $deck4Content = '',
    ) {
    }

    /**
     * The fields of the deck slot $i (1 to NB_DECKS).
     *
     * @return array{id: ?string, isDecklist: ?string, playerName: ?string, questlogdeckContent: string, deckContent: string}
     */
    public function deck(int $i): array
    {
        return match ($i) {
            1 => [
                'id' => $this->deck1Id,
                'isDecklist' => $this->deck1IsDecklist,
                'playerName' => $this->questlogdeck1PlayerName,
                'questlogdeckContent' => $this->questlogdeck1Content,
                'deckContent' => $this->deck1Content,
            ],
            2 => [
                'id' => $this->deck2Id,
                'isDecklist' => $this->deck2IsDecklist,
                'playerName' => $this->questlogdeck2PlayerName,
                'questlogdeckContent' => $this->questlogdeck2Content,
                'deckContent' => $this->deck2Content,
            ],
            3 => [
                'id' => $this->deck3Id,
                'isDecklist' => $this->deck3IsDecklist,
                'playerName' => $this->questlogdeck3PlayerName,
                'questlogdeckContent' => $this->questlogdeck3Content,
                'deckContent' => $this->deck3Content,
            ],
            4 => [
                'id' => $this->deck4Id,
                'isDecklist' => $this->deck4IsDecklist,
                'playerName' => $this->questlogdeck4PlayerName,
                'questlogdeckContent' => $this->questlogdeck4Content,
                'deckContent' => $this->deck4Content,
            ],
            default => throw new \OutOfRangeException(\sprintf('There is no deck slot %d.', $i)),
        };
    }
}
