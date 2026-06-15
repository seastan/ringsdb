<?php

namespace AppBundle\Entity;

class Pack {
    /**
     * @var integer
     */
    private $id;
    /**
     * @var string
     */
    private $code;
    /**
     * @var string
     */
    private $name;
    /**
     * @var integer
     */
    private $position;
    /**
     * @var integer
     */
    private $size;
    /**
     * @var \DateTime
     */
    private $dateCreation;
    /**
     * @var \DateTime
     */
    private $dateUpdate;
    /**
     * @var \DateTime
     */
    private $dateRelease;
    /**
     * @var boolean
     */
    private $isRepackaged = false;
    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $cards;
    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $printings;
    /**
     * @var \AppBundle\Entity\Cycle
     */
    private $cycle;

    /**
     * Constructor
     */
    public function __construct() {
        $this->cards = new \Doctrine\Common\Collections\ArrayCollection();
        $this->printings = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Set isRepackaged
     *
     * @param boolean $isRepackaged
     *
     * @return Pack
     */
    public function setIsRepackaged($isRepackaged) {
        $this->isRepackaged = $isRepackaged;

        return $this;
    }

    /**
     * Get isRepackaged
     *
     * @return boolean
     */
    public function getIsRepackaged() {
        return $this->isRepackaged;
    }

    /**
     * Get id
     *
     * @return integer
     */
    public function getId() {
        return $this->id;
    }

    /**
     * Set code
     *
     * @param string $code
     *
     * @return Pack
     */
    public function setCode($code) {
        $this->code = $code;

        return $this;
    }

    /**
     * Get code
     *
     * @return string
     */
    public function getCode() {
        return $this->code;
    }

    /**
     * Set name
     *
     * @param string $name
     *
     * @return Pack
     */
    public function setName($name) {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName() {
        return $this->name;
    }

    /**
     * Set position
     *
     * @param integer $position
     *
     * @return Pack
     */
    public function setPosition($position) {
        $this->position = $position;

        return $this;
    }

    /**
     * Get position
     *
     * @return integer
     */
    public function getPosition() {
        return $this->position;
    }

    /**
     * Set size
     *
     * @param integer $size
     *
     * @return Pack
     */
    public function setSize($size) {
        $this->size = $size;

        return $this;
    }

    /**
     * Get size
     *
     * @return integer
     */
    public function getSize() {
        return $this->size;
    }

    /**
     * Set dateCreation
     *
     * @param \DateTime $dateCreation
     *
     * @return Pack
     */
    public function setDateCreation($dateCreation) {
        $this->dateCreation = $dateCreation;

        return $this;
    }

    /**
     * Get dateCreation
     *
     * @return \DateTime
     */
    public function getDateCreation() {
        return $this->dateCreation;
    }

    /**
     * Set dateUpdate
     *
     * @param \DateTime $dateUpdate
     *
     * @return Pack
     */
    public function setDateUpdate($dateUpdate) {
        $this->dateUpdate = $dateUpdate;

        return $this;
    }

    /**
     * Get dateUpdate
     *
     * @return \DateTime
     */
    public function getDateUpdate() {
        return $this->dateUpdate;
    }

    /**
     * Set dateRelease
     *
     * @param \DateTime $dateRelease
     *
     * @return Pack
     */
    public function setDateRelease($dateRelease) {
        $this->dateRelease = $dateRelease;

        return $this;
    }

    /**
     * Get dateRelease
     *
     * @return \DateTime
     */
    public function getDateRelease() {
        return $this->dateRelease;
    }

    /**
     * Add card
     *
     * @param \AppBundle\Entity\Card $card
     *
     * @return Pack
     */
    public function addCard(\AppBundle\Entity\Card $card) {
        $this->cards[] = $card;

        return $this;
    }

    /**
     * Remove card
     *
     * @param \AppBundle\Entity\Card $card
     */
    public function removeCard(\AppBundle\Entity\Card $card) {
        $this->cards->removeElement($card);
    }

    /**
     * Get cards
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getCards() {
        return $this->cards;
    }

    /**
     * Add printing
     *
     * @param \AppBundle\Entity\CardPrinting $printing
     *
     * @return Pack
     */
    public function addPrinting(\AppBundle\Entity\CardPrinting $printing) {
        $this->printings[] = $printing;

        return $this;
    }

    /**
     * Remove printing
     *
     * @param \AppBundle\Entity\CardPrinting $printing
     */
    public function removePrinting(\AppBundle\Entity\CardPrinting $printing) {
        $this->printings->removeElement($printing);
    }

    /**
     * Get printings
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getPrintings() {
        return $this->printings;
    }

    /**
     * Set cycle
     *
     * @param \AppBundle\Entity\Cycle $cycle
     *
     * @return Pack
     */
    public function setCycle(\AppBundle\Entity\Cycle $cycle = null) {
        $this->cycle = $cycle;

        return $this;
    }

    /**
     * Get cycle
     *
     * @return \AppBundle\Entity\Cycle
     */
    public function getCycle() {
        return $this->cycle;
    }
}
