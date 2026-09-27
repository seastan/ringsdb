<?php

namespace AppBundle\Entity;

class Decklistsideslot implements \AppBundle\Model\SlotInterface {
    /**
     * @var integer
     */
    private $id;
    /**
     * @var integer
     */
    private $quantity;
    /**
     * @var \AppBundle\Entity\Decklist
     */
    private $decklist;
    /**
     * @var \AppBundle\Entity\Card
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
     * @return Decklistsideslot
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
     * @param \AppBundle\Entity\Decklist $decklist
     *
     * @return Decklistsideslot
     */
    public function setDecklist(\AppBundle\Entity\Decklist $decklist) {
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
     * Set card
     *
     * @param \AppBundle\Entity\Card $card
     *
     * @return Decklistsideslot
     */
    public function setCard(\AppBundle\Entity\Card $card) {
        $this->card = $card;

        return $this;
    }

    /**
     * Get card
     *
     * @return \AppBundle\Entity\Card
     */
    public function getCard() {
        return $this->card;
    }
}
