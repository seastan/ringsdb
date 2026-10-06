<?php

namespace App\Entity;

class Deckslot implements \App\Model\SlotInterface {
    /**
     * @var integer
     */
    private $id;
    /**
     * @var integer
     */
    private $quantity;
    /**
     * @var \App\Entity\Deck
     */
    private $deck;
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
     * @return Deckslot
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
     * Set deck
     *
     * @param \App\Entity\Deck $deck
     *
     * @return Deckslot
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

    /**
     * Set card
     *
     * @param \App\Entity\Card $card
     *
     * @return Deckslot
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
