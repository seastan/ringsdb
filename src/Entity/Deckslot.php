<?php

declare(strict_types=1);

namespace App\Entity;

use App\Model\SlotInterface;

class Deckslot implements SlotInterface
{
    /**
     * @var int|null
     */
    private $id;

    private int $quantity;

    private Deck $deck;

    private Card $card;

    public function __construct(Card $card, Deck $deck, int $quantity)
    {
        $this->card = $card;
        $this->deck = $deck;
        $this->quantity = $quantity;
    }

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
