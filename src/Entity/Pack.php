<?php

namespace App\Entity;

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
     * @var \DateTime|null
     */
    private $dateRelease;
    /**
     * @var boolean
     */
    private $isRepackaged = false;
    /**
     * @var \Doctrine\Common\Collections\Collection<int, \App\Entity\CardPrinting>
     */
    private $printings;
    /**
     * @var \App\Entity\Cycle
     */
    private $cycle;

    /**
     * Constructor
     */
    public function __construct() {
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
     * @param \DateTime|null $dateRelease
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
     * @return \DateTime|null
     */
    public function getDateRelease() {
        return $this->dateRelease;
    }

    /**
     * @return \Doctrine\Common\Collections\Collection<int, \App\Entity\Card>
     */
    public function getCards() {
        return $this->printings->map(function($p) { return $p->getCard(); });
    }

    /**
     * Add printing
     *
     * @param \App\Entity\CardPrinting $printing
     *
     * @return Pack
     */
    public function addPrinting(\App\Entity\CardPrinting $printing) {
        $this->printings[] = $printing;

        return $this;
    }

    /**
     * Remove printing
     *
     * @param \App\Entity\CardPrinting $printing
     * @return void
     */
    public function removePrinting(\App\Entity\CardPrinting $printing) {
        $this->printings->removeElement($printing);
    }

    /**
     * Get printings
     *
     * @return \Doctrine\Common\Collections\Collection<int, \App\Entity\CardPrinting>
     */
    public function getPrintings() {
        return $this->printings;
    }

    /**
     * Set cycle
     *
     * @param \App\Entity\Cycle $cycle
     *
     * @return Pack
     */
    public function setCycle(\App\Entity\Cycle $cycle) {
        $this->cycle = $cycle;

        return $this;
    }

    /**
     * Get cycle
     *
     * @return \App\Entity\Cycle
     */
    public function getCycle() {
        return $this->cycle;
    }
}
