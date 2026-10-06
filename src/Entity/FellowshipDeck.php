<?php

namespace App\Entity;

/**
 * FellowshipDeck
 */
class FellowshipDeck {
    /**
     * @var integer
     */
    private $id;
    /**
     * @var integer
     */
    private $deckNumber;
    /**
     * @var \App\Entity\Fellowship
     */
    private $fellowship;
    /**
     * @var \App\Entity\Deck
     */
    private $deck;

    /**
     * Get id
     *
     * @return integer
     */
    public function getId() {
        return $this->id;
    }

    /**
     * Set deckNumber
     *
     * @param integer $deckNumber
     *
     * @return FellowshipDeck
     */
    public function setDeckNumber($deckNumber) {
        $this->deckNumber = $deckNumber;

        return $this;
    }

    /**
     * Get deckNumber
     *
     * @return integer
     */
    public function getDeckNumber() {
        return $this->deckNumber;
    }

    /**
     * Set fellowship
     *
     * @param \App\Entity\Fellowship $fellowship
     *
     * @return FellowshipDeck
     */
    public function setFellowship(\App\Entity\Fellowship $fellowship) {
        $this->fellowship = $fellowship;

        return $this;
    }

    /**
     * Get fellowship
     *
     * @return \App\Entity\Fellowship
     */
    public function getFellowship() {
        return $this->fellowship;
    }

    /**
     * Set deck
     *
     * @param \App\Entity\Deck $deck
     *
     * @return FellowshipDeck
     */
    public function setDeck(\App\Entity\Deck $deck) {
        $this->deck = $deck;

        return $this;
    }

    /**
     * Get deck
     *
     * @return \App\Entity\Deck
     */
    public function getDeck() {
        return $this->deck;
    }
}
