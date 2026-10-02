<?php

declare(strict_types=1);

namespace App\Model;

/**
 * Interface for an entity with a Card and a Quantity.
 */
interface SlotInterface
{
    /**
     * Get card.
     */
    public function getCard(): \App\Entity\Card;

    /**
     * Get quantity.
     */
    public function getQuantity(): int;

    /**
     * Set quantity.
     *
     * @param int $quantity
     */
    public function setQuantity($quantity): self;
}
