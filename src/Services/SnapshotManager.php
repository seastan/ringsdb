<?php

namespace App\Services;

use App\Entity\Deck;

class SnapshotManager
{
    // Set the deck content to the QuestlogDeck snapshot
    private Decks $decks;

    public function __construct(
        Decks $decks
    ) {
        $this->decks = $decks;
    }

    public function setSnapshot($questlog): void
    {
        $questlog_decks = $questlog->getDecks();
        $decks_service = $this->decks;
        foreach ($questlog_decks as $questlog_deck) {
            $deck = $questlog_deck->getDeck();
            if (!$deck) {
                $deck = new Deck();
                $deck->setName('[deleted]');
                $questlog_deck->setDeck($deck);
            }
            $questlogdeck_content = (array) json_decode($questlog_deck->getContent());
            $decks_service->setSlots($deck, $questlogdeck_content);
        }
    }

    public function setSnapshots($questlogs): void
    {
        foreach ($questlogs as $questlog) {
            $this->setSnapshot($questlog);
        }
    }
}
