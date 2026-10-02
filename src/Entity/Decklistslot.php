<?php

declare(strict_types=1);

namespace App\Entity;

use App\Model\SlotInterface;

class Decklistslot implements SlotInterface
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
     * @var Decklist
     */
    private $decklist;
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
    public function setQuantity($quantity): Decklistslot
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
     * Set decklist.
     */
    public function setDecklist(Decklist $decklist): Decklistslot
    {
        $this->decklist = $decklist;

        return $this;
    }

    /**
     * Get decklist.
     */
    public function getDecklist(): Decklist
    {
        return $this->decklist;
    }

    /**
     * Set card.
     */
    public function setCard(Card $card): Decklistslot
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
