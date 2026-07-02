<?php

namespace AppBundle\Entity;

class UserCustomPackCard {

    private $id;
    private $customPack;
    private $card;
    private $quantity = 1;

    public function getId() { return $this->id; }

    public function getCustomPack() { return $this->customPack; }
    public function setCustomPack(UserCustomPack $customPack) { $this->customPack = $customPack; return $this; }

    public function getCard() { return $this->card; }
    public function setCard(Card $card) { $this->card = $card; return $this; }

    public function getQuantity() { return $this->quantity; }
    public function setQuantity($quantity) { $this->quantity = max(1, (int)$quantity); return $this; }
}
