<?php

namespace App\Entity;

use App\Entity\Card;
use App\Entity\UserCustomPack;

class UserCustomPackCard {

    /**
     * @var int
     */
    private $id;
    /**
     * @var \App\Entity\UserCustomPack
     */
    private $customPack;
    /**
     * @var \App\Entity\Card
     */
    private $card;
    /**
     * @var int
     */
    private $quantity = 1;

    /**
     * @return int
     */
    public function getId() { return $this->id; }

    /**
     * @return \App\Entity\UserCustomPack
     */
    public function getCustomPack() { return $this->customPack; }
    /**
     * @return $this
     */
    public function setCustomPack(UserCustomPack $customPack) { $this->customPack = $customPack; return $this; }

    /**
     * @return \App\Entity\Card
     */
    public function getCard() { return $this->card; }
    /**
     * @return $this
     */
    public function setCard(Card $card) { $this->card = $card; return $this; }

    /**
     * @return int
     */
    public function getQuantity() { return $this->quantity; }
    /**
     * @param mixed $quantity
     * @return $this
     */
    public function setQuantity($quantity) { $this->quantity = max(1, (int)$quantity); return $this; }
}
