<?php

declare(strict_types=1);

namespace App\Entity;

class Deckslot implements \App\Model\SlotInterface
{
    /**
     * @var int|null
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
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Set quantity.
     *
     * @param int $quantity
     */
    public function setQuantity($quantity): Deckslot
    {
        $this->quantity = $quantity;

        return $this;
    }

    /**
     * Get quantity.
     */
    public function getQuantity(): int
    {
        return $this->quantity;
    }

    /**
     * Set deck.
     */
    public function setDeck(Deck $deck): Deckslot
    {
        $this->deck = $deck;

        return $this;
    }

    /**
     * Get deck.
     */
    public function getDeck(): Deck
    {
        return $this->deck;
    }

    /**
     * Set card.
     */
    public function setCard(Card $card): Deckslot
    {
        $this->card = $card;

        return $this;
    }

    /**
     * Get card.
     */
    public function getCard(): Card
    {
        return $this->card;
    }
}
