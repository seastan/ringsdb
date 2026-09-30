<?php

namespace AppBundle\Model;

/**
 * Interface for an entity with a Card and a Quantity
 */
interface SlotInterface {
    /**
     * Get card
     *
     * @return \AppBundle\Entity\Card
     */
    public function getCard();

    /**
     * Get quantity
     *
     * @return integer
     */
    public function getQuantity();

    /**
     * Set quantity
     *
     * @param integer $quantity
     * @return self
     */
    public function setQuantity($quantity);
}
