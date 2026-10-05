<?php

declare(strict_types=1);

namespace App\Entity;

use App\Model\SlotInterface;

class Decksideslot implements SlotInterface
{
    /**
     * @var int|null
     */
    private $id;

    private int $quantity;

    private \App\Entity\Deck $deck;

    private \App\Entity\Card $card;

    public function __construct(Deck $deck, Card $card, int $quantity)
    {
        $this->deck = $deck;
        $this->card = $card;
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
    public function setQuantity($quantity): Decksideslot
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
    public function setDeck(Deck $deck): Decksideslot
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
    public function setCard(Card $card): Decksideslot
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
