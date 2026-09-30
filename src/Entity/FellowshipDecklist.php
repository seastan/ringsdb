<?php

namespace App\Entity;

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
     * @var \App\Entity\Fellowship
     */
    private $fellowship;
    /**
     * @var \App\Entity\Decklist
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
     * @param \App\Entity\Fellowship $fellowship
     *
     * @return FellowshipDecklist
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
     * Get decklist
     *
     * @return \App\Entity\Decklist
     */
    public function getDecklist() {
        return $this->decklist;
    }

    /**
     * Set decklist
     *
     * @param \App\Entity\Decklist $decklist
     *
     * @return FellowshipDecklist
     */
    public function setDecklist(\App\Entity\Decklist $decklist) {
        $this->decklist = $decklist;

        return $this;
    }
}
