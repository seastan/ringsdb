<?php

namespace App\Entity;

class Decklistsideslot implements \App\Model\SlotInterface
{
    /**
     * @var int
     */
    private $id;
    /**
     * @var int
     */
    private $quantity;
    /**
     * @var Decklist
     */
    private $decklist;
    /**
     * @var Card
     */
    private $card;

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
     * Set quantity.
     *
     * @param int $quantity
     *
     * @return Decklistsideslot
     */
    public function setQuantity($quantity)
    {
        $this->quantity = $quantity;

        return $this;
    }

    /**
     * Get quantity.
     *
     * @return int
     */
    public function getQuantity()
    {
        return $this->quantity;
    }

    /**
     * Set decklist.
     *
     * @return Decklistsideslot
     */
    public function setDecklist(Decklist $decklist)
    {
        $this->decklist = $decklist;

        return $this;
    }

    /**
     * Get decklist.
     *
     * @return Decklist
     */
    public function getDecklist()
    {
        return $this->decklist;
    }

    /**
     * Set card.
     *
     * @return Decklistsideslot
     */
    public function setCard(Card $card)
    {
        $this->card = $card;

        return $this;
    }

    /**
     * Get card.
     *
     * @return Card
     */
    public function getCard()
    {
        return $this->card;
    }
}
