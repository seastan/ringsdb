<?php

namespace App\Entity;

class Decksideslot implements \App\Model\SlotInterface
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
     * @var Deck
     */
    private $deck;
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
     * @return Decksideslot
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
     * Set deck.
     *
     * @return Decksideslot
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

    /**
     * Set card.
     *
     * @return Decksideslot
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
