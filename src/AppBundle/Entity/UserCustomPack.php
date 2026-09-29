<?php

namespace AppBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;

class UserCustomPack {

    /**
     * @var int
     */
    private $id;
    /**
     * @var \AppBundle\Entity\User
     */
    private $user;
    /**
     * @var string
     */
    private $name;
    /**
     * @var string
     */
    private $code;
    /**
     * @var bool
     */
    private $isEnabled = true;
    /**
     * @var bool
     */
    private $isPublished = false;
    /**
     * @var \DateTime
     */
    private $createdAt;
    /**
     * @var \DateTime
     */
    private $updatedAt;
    /**
     * @var \Doctrine\Common\Collections\Collection<int, \AppBundle\Entity\UserCustomPackCard>
     */
    private $cards;

    public function __construct() {
        $this->cards = new ArrayCollection();
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
    }

    /**
     * @return int
     */
    public function getId() { return $this->id; }

    /**
     * @return \AppBundle\Entity\User
     */
    public function getUser() { return $this->user; }
    /**
     * @param mixed $user
     * @return $this
     */
    public function setUser($user) { $this->user = $user; return $this; }

    /**
     * @return string
     */
    public function getName() { return $this->name; }
    /**
     * @param mixed $name
     * @return $this
     */
    public function setName($name) { $this->name = $name; return $this; }

    /**
     * @return string
     */
    public function getCode() { return $this->code; }
    /**
     * @param mixed $code
     * @return $this
     */
    public function setCode($code) { $this->code = $code; return $this; }

    /**
     * @return bool
     */
    public function getIsEnabled() { return $this->isEnabled; }
    /**
     * @param mixed $isEnabled
     * @return $this
     */
    public function setIsEnabled($isEnabled) { $this->isEnabled = (bool)$isEnabled; return $this; }

    /**
     * @return bool
     */
    public function getIsPublished() { return $this->isPublished; }
    /**
     * @param mixed $isPublished
     * @return $this
     */
    public function setIsPublished($isPublished) { $this->isPublished = (bool)$isPublished; return $this; }

    /**
     * @return \DateTime
     */
    public function getCreatedAt() { return $this->createdAt; }
    /**
     * @param mixed $createdAt
     * @return $this
     */
    public function setCreatedAt($createdAt) { $this->createdAt = $createdAt; return $this; }

    /**
     * @return \DateTime
     */
    public function getUpdatedAt() { return $this->updatedAt; }
    /**
     * @param mixed $updatedAt
     * @return $this
     */
    public function setUpdatedAt($updatedAt) { $this->updatedAt = $updatedAt; return $this; }

    /**
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getCards() { return $this->cards; }

    /**
     * @return $this
     */
    public function clearCards() {
        $this->cards->clear();
        return $this;
    }

    /**
     * @return $this
     */
    public function addCard(UserCustomPackCard $card) {
        $this->cards->add($card);
        return $this;
    }
}
