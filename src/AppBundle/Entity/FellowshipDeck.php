<?php

namespace AppBundle\Entity;

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
     * @var \AppBundle\Entity\Fellowship
     */
    private $fellowship;
    /**
     * @var \AppBundle\Entity\Deck
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
     * @param \AppBundle\Entity\Fellowship $fellowship
     *
     * @return FellowshipDeck
     */
    public function setFellowship(\AppBundle\Entity\Fellowship $fellowship = null) {
        $this->fellowship = $fellowship;

        return $this;
    }

    /**
     * Get fellowship
     *
     * @return \AppBundle\Entity\Fellowship
     */
    public function getFellowship() {
        return $this->fellowship;
    }

    /**
     * Set deck
     *
     * @param \AppBundle\Entity\Deck $deck
     *
     * @return FellowshipDeck
     */
    public function setDeck(\AppBundle\Entity\Deck $deck = null) {
        $this->deck = $deck;

        return $this;
    }

    /**
     * Get deck
     *
     * @return \AppBundle\Entity\Deck
     */
    public function getDeck() {
        return $this->deck;
    }
}
