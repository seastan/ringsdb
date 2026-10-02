<?php

declare(strict_types=1);

namespace App\Entity;

/**
 * FellowshipDeck.
 */
class FellowshipDeck
{
    /**
     * @var int
     */
    private $id;
    /**
     * @var int
     */
    private $deckNumber;
    /**
     * @var Fellowship
     */
    private $fellowship;
    /**
     * @var Deck
     */
    private $deck;

    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set deckNumber.
     *
     * @param int $deckNumber
     *
     * @return FellowshipDeck
     */
    public function setDeckNumber($deckNumber)
    {
        $this->deckNumber = $deckNumber;

        return $this;
    }

    /**
     * Get deckNumber.
     *
     * @return int
     */
    public function getDeckNumber()
    {
        return $this->deckNumber;
    }

    /**
     * Set fellowship.
     *
     * @return FellowshipDeck
     */
    public function setFellowship(Fellowship $fellowship)
    {
        $this->fellowship = $fellowship;

        return $this;
    }

    /**
     * Get fellowship.
     *
     * @return Fellowship
     */
    public function getFellowship()
    {
        return $this->fellowship;
    }

    /**
     * Set deck.
     *
     * @return FellowshipDeck
     */
    public function setDeck(Deck $deck)
    {
        $this->deck = $deck;

        return $this;
    }

    /**
     * Get deck.
     *
     * @return Deck
     */
    public function getDeck()
    {
        return $this->deck;
    }
}
