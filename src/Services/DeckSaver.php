<?php

declare(strict_types=1);

namespace App\Services;

use App\Entity\Deck;
use App\Entity\User;
use App\Helper\StringSanitizer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

/**
 * Saves a deck from the deck builder (/deck/save), a clone, a copy of a decklist or a file import.
 */
class DeckSaver
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly Decks $decks
    ) {
    }

    /**
     * The name, description and tags are user input, sanitized here.
     *
     * @param Deck|null $deck       the deck to update, or null for a new deck
     * @param Deck|null $sourceDeck the deck the changes are computed from (the edited deck, or the
     *                              original of a copy)
     * @param array{main: array<int|string, int>, side: array<int|string, int>} $content
     *
     * @throws UnprocessableEntityHttpException when the user has reached their maximum number of decks
     */
    public function save(User $user, ?Deck $deck, ?Deck $sourceDeck, array $content, string $name, string $description = '', string $tags = '', ?int $decklistId = null): Deck
    {
        if (count($user->getDecks()) > $user->getMaxNbDecks()) {
            throw new UnprocessableEntityHttpException('You have reached the maximum number of decks allowed. Delete some decks or increase your reputation.');
        }

        $deck ??= new Deck($user);
        $name = StringSanitizer::sanitize($name, false);
        if ('' === $name) {
            $name = 'Untitled Deck';
        }

        $this->decks->saveDeck($user, $deck, $decklistId, $name, trim($description), StringSanitizer::sanitize($tags, false), $content, $sourceDeck);
        $this->entityManager->flush();

        return $deck;
    }
}
