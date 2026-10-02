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
     *
     * @return \App\Entity\Card
     */
    public function getCard();

    /**
     * Get quantity.
     *
     * @return int
     */
    public function getQuantity();

    /**
     * Set quantity.
     *
     * @param int $quantity
     *
     * @return self
     */
    public function setQuantity($quantity);
}
