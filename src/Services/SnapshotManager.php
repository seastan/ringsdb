<?php

namespace App\Services;

use App\Entity\Card;
use App\Entity\Deck;
use App\Entity\Decksideslot;
use App\Entity\Deckslot;
use App\Entity\Questlog;
use App\Entity\QuestlogDeck;
use Doctrine\ORM\EntityManagerInterface;

class SnapshotManager
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly CardManager $cardManager,
    ) {
    }

    public function setSnapshot(Questlog $questlog): void
    {
        $questlog_decks = $questlog->getDecks();
        foreach ($questlog_decks as $questlog_deck) {
            $deck = $questlog_deck->getDeck();
            if (!$deck) {
                $deck = new Deck($questlog->getUser());
                $deck->setName('[deleted]');
                $questlog_deck->setDeck($deck);
            }

            $this->applySnapshot($deck, $questlog_deck);
        }
    }

    /**
     * @param iterable<Questlog> $questlogs
     */
    public function setSnapshots($questlogs): void
    {
        foreach ($questlogs as $questlog) {
            $this->setSnapshot($questlog);
        }
    }

    public function applySnapshot(Deck $deck, QuestlogDeck $questlogDeck): void
    {
        $content = json_decode($questlogDeck->getContent(), true);

        $cards = [];

        foreach ($content['main'] as $card_code => $qty) {
            $card = $this->cardManager->findCardByCode((string) $card_code);

            if (!$card instanceof Card) {
                continue;
            }

            $cards[$card_code] = $card;

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

            $card = $cards[$card_code];
            $slot = new Deckslot($deck, $card, $qty);
            $deck->addSlot($slot);
        }

        foreach ($content['side'] as $card_code => $qty) {
            if (!isset($cards[$card_code])) {
                continue;
            }

            $card = $cards[$card_code];
            $slot = new Decksideslot($deck, $card, $qty);
            $deck->addSideslot($slot);
        }
    }
}
