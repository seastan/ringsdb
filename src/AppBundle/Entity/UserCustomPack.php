<?php

namespace AppBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;

class UserCustomPack {

    private $id;
    private $user;
    private $name;
    private $code;
    private $isEnabled = true;
    private $createdAt;
    private $updatedAt;
    private $cards;

    public function __construct() {
        $this->cards = new ArrayCollection();
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
    }

    public function getId() { return $this->id; }

    public function getUser() { return $this->user; }
    public function setUser($user) { $this->user = $user; return $this; }

    public function getName() { return $this->name; }
    public function setName($name) { $this->name = $name; return $this; }

    public function getCode() { return $this->code; }
    public function setCode($code) { $this->code = $code; return $this; }

    public function getIsEnabled() { return $this->isEnabled; }
    public function setIsEnabled($isEnabled) { $this->isEnabled = (bool)$isEnabled; return $this; }

    public function getCreatedAt() { return $this->createdAt; }
    public function setCreatedAt($createdAt) { $this->createdAt = $createdAt; return $this; }

    public function getUpdatedAt() { return $this->updatedAt; }
    public function setUpdatedAt($updatedAt) { $this->updatedAt = $updatedAt; return $this; }

    public function getCards() { return $this->cards; }

    public function clearCards() {
        $this->cards->clear();
        return $this;
    }

    public function addCard(UserCustomPackCard $card) {
        $this->cards->add($card);
        return $this;
    }
}
