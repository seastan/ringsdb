<?php

namespace App\Services;

use App\Entity\Deck;
use App\Entity\Questlog;

class SnapshotManager
{
    public function __construct(private Decks $decks)
    {
    }

    public function setSnapshot(Questlog $questlog): void
    {
        $questlog_decks = $questlog->getDecks();
        $decks_service = $this->decks;
        foreach ($questlog_decks as $questlog_deck) {
            $deck = $questlog_deck->getDeck();
            if (!$deck) {
                $deck = new Deck($questlog->getUser());
                $deck->setName('[deleted]');
                $questlog_deck->setDeck($deck);
            }

            $questlogdeck_content = json_decode($questlog_deck->getContent(), true);
            $decks_service->setSlots($deck, $questlogdeck_content);
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
}
