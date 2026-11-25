<?php

declare(strict_types=1);

namespace App\Services;

use App\Entity\Card;
use App\Entity\Deck;
use App\Entity\Deckchange;
use App\Entity\Decklist;
use App\Entity\Decksideslot;
use App\Entity\Deckslot;
use App\Entity\Pack;
use App\Entity\Sphere;
use App\Entity\Type;
use App\Entity\User;
use App\Exception\TooManyDecksException;
use App\Helper\DeckValidationHelper;
use App\Helper\StringSanitizer;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Saves a deck from the deck builder (/deck/save and /deck/save-ajax), a clone, a copy of a decklist
 * or a file import.
 */
class DeckSaver
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly Decks $decks,
        private readonly DeckValidationHelper $deckValidationHelper,
        private readonly Diff $diff,
        private readonly CardManager $cardManager,
    ) {
    }

    /**
     * The name, description and tags are user input, sanitized here.
     *
     * @param Deck|null                                                         $deck       the deck to update, or null for a new deck
     * @param Deck|null                                                         $sourceDeck the deck the changes are computed from (the edited deck, or the
     *                                                                                      original of a copy)
     * @param array{main: array<int|string, int>, side: array<int|string, int>} $content
     *
     * @throws TooManyDecksException
     */
    public function save(User $user, array $content, string $name = '', string $description = '', string $tags = '', ?Deck $deck = null, ?Deck $sourceDeck = null, ?Decklist $decklist = null): Deck
    {
        if (count($user->getDecks()) > $user->getMaxNbDecks()) {
            throw new TooManyDecksException();
        }

        $deck ??= new Deck($user);
        $name = StringSanitizer::sanitize($name, false);
        if ('' === $name) {
            $name = 'Untitled Deck';
        }

        if ($decklist instanceof Decklist) {
            $deck->setParent($decklist);
        }

        $deck->setName($name);
        $deck->setDescriptionMd(trim($description));
        $deck->setUser($user);
        $deck->setMinorVersion($deck->getMinorVersion() + 1);

        $cards = [];
        /* @var $latestPack Pack */
        $latestPack = null;
        $spheres = [];

        foreach ($content['main'] as $card_code => $qty) {
            $card = $this->cardManager->findCardByCode((string) $card_code);

            if (!$card instanceof Card) {
                continue;
            }

            $pack = $card->getPack();
            if ($pack instanceof Pack) {
                if (!$latestPack instanceof Pack) {
                    $latestPack = $pack;
                } elseif ($pack->isLaterThan($latestPack)) {
                    $latestPack = $pack;
                }
            }

            $cards[$card_code] = $card;
            if ($card->getType() instanceof Type
                && $card->getSphere() instanceof Sphere
                && 'hero' === $card->getType()->getCode()) {
                $spheres[] = $card->getSphere()->getCode();
            }

            if ($qty > $card->getDeckLimit()) {
                $content['main'][$card_code] = $card->getDeckLimit();
            }
        }

        foreach ($content['side'] as $card_code => $qty) {
            $card = $this->cardManager->findCardByCode((string) $card_code);

            if (!$card instanceof Card) {
                continue;
            }

            $cards[$card_code] = $card;

            if ($qty > $card->getDeckLimit()) {
                $content['side'][$card_code] = $card->getDeckLimit();
            }
        }

        $deck->setLastPack($latestPack);
        $tags = $this->decks->normalizeTags(StringSanitizer::sanitize($tags, false));
        if ([] === $tags) {
            // tags can never be empty. if it is we put spheres in
            $tags = $this->decks->normalizeTags($spheres);
        }

        $deck->setTags(implode(' ', $tags));
        $this->entityManager->persist($deck);

        // on the deck content
        if ($sourceDeck instanceof Deck) {
            // compute diff between current content and saved content
            [$listings] = $this->diff->diffContents([
                $content['main'],
                $sourceDeck->getSlots()->getContent(),
            ]);

            [$sideListings] = $this->diff->diffContents([
                $content['side'],
                $sourceDeck->getSideslots()->getContent(),
            ]);

            $listings[2] = $sideListings[0];
            $listings[3] = $sideListings[1];

            // remove all change (autosave) since last deck update (changes are sorted)
            $changes = $this->decks->getUnsavedChanges($deck);
            foreach ($changes as $change) {
                $this->entityManager->remove($change);
            }

            // save new change unless empty
            if (count($listings[0]) || count($listings[1]) || count($listings[2]) || count($listings[3])) {
                $change = new Deckchange($deck);
                $change->setVariation((string) json_encode($listings));
                $change->setIsSaved(true);
                $change->setVersion($deck->getVersion());
                $this->entityManager->persist($change);
            }

            // copy version
            $deck->setMajorVersion($sourceDeck->getMajorVersion());
            $deck->setMinorVersion($sourceDeck->getMinorVersion());
        }

        foreach ($deck->getSlots() as $slot) {
            $deck->removeSlot($slot);
            $this->entityManager->remove($slot);
        }

        foreach ($deck->getSideslots() as $slot) {
            $deck->removeSideslot($slot);
            $this->entityManager->remove($slot);
        }

        foreach ($content['main'] as $card_code => $qty) {
            if (!isset($cards[$card_code])) {
                continue;
            }

            $deck->addSlot(new Deckslot($deck, $cards[$card_code], $qty));
        }

        foreach ($content['side'] as $card_code => $qty) {
            if (!isset($cards[$card_code])) {
                continue;
            }

            $deck->addSideslot(new Decksideslot($deck, $cards[$card_code], $qty));
        }

        $deck->setProblem($this->deckValidationHelper->findProblem($deck));
        $this->entityManager->flush();

        return $deck;
    }

    /**
     * Copies another user's deck (with its name, description and parent decklist) for $user.
     *
     * @throws TooManyDecksException
     */
    public function cloneDeck(User $user, Deck $deck): Deck
    {
        return $this->save(
            $user,
            $deck->getContent(),
            $deck->getName().' (clone)',
            (string) $deck->getDescriptionMd(),
            decklist: $deck->getParent()
        );
    }
}
