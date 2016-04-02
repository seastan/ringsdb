<?php

namespace AppBundle\Entity;

/**
 * FellowshipDecklist
 */
class FellowshipDecklist {
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
     * @var \AppBundle\Entity\Decklist
     */
    private $decklist;

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
     * @return FellowshipDecklist
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
     * @return FellowshipDecklist
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
     * Set decklist
     *
     * @param \AppBundle\Entity\Decklist $decklist
     *
     * @return FellowshipDecklist
     */
    public function setDeck(\AppBundle\Entity\Decklist $decklist = null) {
        $this->decklist = $decklist;

        return $this;
    }

    /**
     * Get decklist
     *
     * @return \AppBundle\Entity\Decklist
     */
    public function getDecklist() {
        return $this->decklist;
    }

    /**
     * Set decklist
     *
     * @param \AppBundle\Entity\Decklist $decklist
     *
     * @return FellowshipDecklist
     */
    public function setDecklist(\AppBundle\Entity\Decklist $decklist = null) {
        $this->decklist = $decklist;

        return $this;
    }
}
