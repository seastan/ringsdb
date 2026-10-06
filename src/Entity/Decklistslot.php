<?php

namespace App\Entity;

class Decklistslot implements \App\Model\SlotInterface {
    /**
     * @var integer
     */
    private $id;
    /**
     * @var integer
     */
    private $quantity;
    /**
     * @var \App\Entity\Decklist
     */
    private $decklist;
    /**
     * @var \App\Entity\Card
     */
    private $card;

    /**
     * Get id
     *
     * @return integer
     */
    public function getId() {
        return $this->id;
    }

    /**
     * Set quantity
     *
     * @param integer $quantity
     *
     * @return Decklistslot
     */
    public function setQuantity($quantity) {
        $this->quantity = $quantity;

        return $this;
    }

    /**
     * Get quantity
     *
     * @return integer
     */
    public function getQuantity() {
        return $this->quantity;
    }

    /**
     * Set decklist
     *
     * @param \App\Entity\Decklist $decklist
     *
     * @return Decklistslot
     */
    public function setDecklist(\App\Entity\Decklist $decklist) {
        $this->decklist = $decklist;

        return $this;
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
     * Set card
     *
     * @param \App\Entity\Card $card
     *
     * @return Decklistslot
     */
    public function setCard(\App\Entity\Card $card) {
        $this->card = $card;

        return $this;
    }

    /**
     * Get card
     *
     * @return \App\Entity\Card
     */
    public function getCard() {
        return $this->card;
    }
}
