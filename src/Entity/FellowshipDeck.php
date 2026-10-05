<?php

declare(strict_types=1);

namespace App\Entity;

/**
 * FellowshipDeck.
 */
class FellowshipDeck
{
    /**
     * @var int|null
     */
    private $id;

    private int $deckNumber;

    private \App\Entity\Fellowship $fellowship;

    private \App\Entity\Deck $deck;

    public function __construct(Fellowship $fellowship, Deck $deck, int $deckNumber)
    {
        $this->fellowship = $fellowship;
        $this->deck = $deck;
        $this->deckNumber = $deckNumber;
    }

    /**
     * Get id.
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Set deckNumber.
     *
     * @param int $deckNumber
     */
    public function setDeckNumber($deckNumber): FellowshipDeck
    {
        $this->deckNumber = $deckNumber;

        return $this;
    }

    /**
     * Get deckNumber.
     */
    public function getDeckNumber(): int
    {
        return $this->deckNumber;
    }

    /**
     * Set fellowship.
     */
    public function setFellowship(Fellowship $fellowship): FellowshipDeck
    {
        $this->fellowship = $fellowship;

        return $this;
    }

    /**
     * Get fellowship.
     */
    public function getFellowship(): Fellowship
    {
        return $this->fellowship;
    }

    /**
     * Set deck.
     */
    public function setDeck(Deck $deck): FellowshipDeck
    {
        $this->deck = $deck;

        return $this;
    }

    /**
     * Get deck.
     */
    public function getDeck(): Deck
    {
        return $this->deck;
    }
}
