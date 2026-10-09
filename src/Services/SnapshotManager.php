<?php

namespace App\Services;

use App\Entity\Card;
use App\Entity\Deck;
use App\Entity\Decklist;
use App\Entity\Decksideslot;
use App\Entity\Deckslot;
use App\Entity\Questlog;
use App\Entity\QuestlogDeck;
use App\Repository\DeckRepository;
use Doctrine\ORM\EntityManagerInterface;

class SnapshotManager
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly CardManager $cardManager,
        private readonly DeckRepository $deckRepository,
    ) {
    }

    /**
     * Rewrites the private deck of each quest log deck with its snapshot, for the quest log lists.
     *
     * The lists display the decklist of a quest log deck when it has one, and its deck only
     * otherwise: the decks of the quest log decks with a decklist are left alone. The cards of all
     * the snapshots are looked up together, and the slots of the decks loaded together.
     *
     * @param iterable<Questlog> $questlogs
     */
    public function setSnapshots(iterable $questlogs): void
    {
        $questlogDecks = [];
        $contents = [];
        $codes = [];
        foreach ($questlogs as $questlog) {
            foreach ($questlog->getDecks() as $questlogDeck) {
                if ($questlogDeck->getDecklist() instanceof Decklist) {
                    continue;
                }

                if (!$questlogDeck->getDeck() instanceof Deck) {
                    $deck = new Deck($questlog->getUser());
                    $deck->setName('[deleted]');
                    $questlogDeck->setDeck($deck);
                }

                $content = $this->decodeContent($questlogDeck);
                $questlogDecks[] = $questlogDeck;
                $contents[] = $content;
                $codes = array_merge($codes, $this->codes($content));
            }
        }

        $cards = $this->cardManager->findCardsByCodes($codes);
        $this->deckRepository->loadSlots(array_filter(
            array_map(fn (QuestlogDeck $questlogDeck): ?Deck => $questlogDeck->getDeck(), $questlogDecks),
            fn (?Deck $deck): bool => null !== $deck?->getId(),
        ));

        foreach ($questlogDecks as $i => $questlogDeck) {
            /** @var Deck $deck */
            $deck = $questlogDeck->getDeck();
            $this->fillDeck($deck, $contents[$i], $cards);
        }
    }

    public function applySnapshot(Deck $deck, QuestlogDeck $questlogDeck): void
    {
        $content = $this->decodeContent($questlogDeck);
        $this->fillDeck($deck, $content, $this->cardManager->findCardsByCodes($this->codes($content)));
    }

    /**
     * @return array{main: array<int|string, int>, side: array<int|string, int>}
     */
    private function decodeContent(QuestlogDeck $questlogDeck): array
    {
        $content = json_decode($questlogDeck->getContent(), true);

        return ['main' => $content['main'] ?? [], 'side' => $content['side'] ?? []];
    }

    /**
     * @param array{main: array<int|string, int>, side: array<int|string, int>} $content
     *
     * @return list<string>
     */
    private function codes(array $content): array
    {
        return array_map(strval(...), array_merge(array_keys($content['main']), array_keys($content['side'])));
    }

    /**
     * Replaces the slots of the deck with the snapshot. Quantities are capped at the deck limit
     * of the card; unknown codes are dropped.
     *
     * @param array{main: array<int|string, int>, side: array<int|string, int>} $content
     * @param array<string, Card>                                               $cards   by code
     */
    private function fillDeck(Deck $deck, array $content, array $cards): void
    {
        foreach (['main', 'side'] as $part) {
            foreach ($content[$part] as $card_code => $qty) {
                $card = $cards[(string) $card_code] ?? null;
                if (!$card instanceof Card) {
                    unset($content[$part][$card_code]);
                    continue;
                }

                if ($qty > $card->getDeckLimit()) {
                    $content[$part][$card_code] = $card->getDeckLimit();
                }
            }
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
            $slot = new Deckslot($deck, $cards[(string) $card_code], $qty);
            $deck->addSlot($slot);
        }

        foreach ($content['side'] as $card_code => $qty) {
            $slot = new Decksideslot($deck, $cards[(string) $card_code], $qty);
            $deck->addSideslot($slot);
        }
    }
}
