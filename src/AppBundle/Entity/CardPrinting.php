<?php

namespace AppBundle\Entity;

class CardPrinting {
    /**
     * @var integer
     */
    private $id;
    /**
     * @var integer
     */
    private $position;
    /**
     * @var integer
     */
    private $quantity;
    /**
     * @var string
     */
    private $illustrator;
    /**
     * @var string
     */
    private $octgnid;
    /**
     * @var string
     */
    private $imageCode;
    /**
     * @var string
     */
    private $traits;
    /**
     * @var string
     */
    private $text;
    /**
     * @var integer
     */
    private $cost;
    /**
     * @var integer
     */
    private $threat;
    /**
     * @var integer
     */
    private $willpower;
    /**
     * @var integer
     */
    private $attack;
    /**
     * @var integer
     */
    private $defense;
    /**
     * @var integer
     */
    private $health;
    /**
     * @var integer
     */
    private $victory;
    /**
     * @var integer
     */
    private $quest;
    /**
     * @var \DateTime
     */
    private $dateCreation;
    /**
     * @var \DateTime
     */
    private $dateUpdate;
    /**
     * @var \AppBundle\Entity\Card
     */
    private $card;
    /**
     * @var \AppBundle\Entity\Pack
     */
    private $pack;

    /**
     * Get id
     *
     * @return integer
     */
    public function getId() {
        return $this->id;
    }

    /**
     * Set position
     *
     * @param integer $position
     *
     * @return CardPrinting
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
     * Set quantity
     *
     * @param integer $quantity
     *
     * @return CardPrinting
     */
    public function setQuantity($quantity) {
        $this->quantity = $quantity;

        return $this;
    }

    /**
     * Get quantity
     *
     * @return integer
     */
    public function getQuantity() {
        return $this->quantity;
    }

    /**
     * Set illustrator
     *
     * @param string $illustrator
     *
     * @return CardPrinting
     */
    public function setIllustrator($illustrator) {
        $this->illustrator = $illustrator;

        return $this;
    }

    /**
     * Get illustrator
     *
     * @return string
     */
    public function getIllustrator() {
        return $this->illustrator;
    }

    /**
     * Set octgnid
     *
     * @param string $octgnid
     *
     * @return CardPrinting
     */
    public function setOctgnid($octgnid) {
        $this->octgnid = $octgnid;

        return $this;
    }

    /**
     * Get octgnid
     *
     * @return string
     */
    public function getOctgnid() {
        return $this->octgnid;
    }

    /**
     * Set imageCode
     *
     * @param string $imageCode
     *
     * @return CardPrinting
     */
    public function setImageCode($imageCode) {
        $this->imageCode = $imageCode;

        return $this;
    }

    /**
     * Get imageCode
     *
     * @return string
     */
    public function getImageCode() {
        return $this->imageCode;
    }

    /**
     * Set traits
     *
     * @param string $traits
     *
     * @return CardPrinting
     */
    public function setTraits($traits) {
        $this->traits = $traits;

        return $this;
    }

    /**
     * Get traits
     *
     * @return string
     */
    public function getTraits() {
        return $this->traits;
    }

    /**
     * Set text
     *
     * @param string $text
     *
     * @return CardPrinting
     */
    public function setText($text) {
        $this->text = $text;

        return $this;
    }

    /**
     * Get text
     *
     * @return string
     */
    public function getText() {
        return $this->text;
    }

    /**
     * Set cost
     *
     * @param integer $cost
     *
     * @return CardPrinting
     */
    public function setCost($cost) {
        $this->cost = $cost;

        return $this;
    }

    /**
     * Get cost
     *
     * @return integer
     */
    public function getCost() {
        return $this->cost;
    }

    /**
     * Set threat
     *
     * @param integer $threat
     *
     * @return CardPrinting
     */
    public function setThreat($threat) {
        $this->threat = $threat;

        return $this;
    }

    /**
     * Get threat
     *
     * @return integer
     */
    public function getThreat() {
        return $this->threat;
    }

    /**
     * Set willpower
     *
     * @param integer $willpower
     *
     * @return CardPrinting
     */
    public function setWillpower($willpower) {
        $this->willpower = $willpower;

        return $this;
    }

    /**
     * Get willpower
     *
     * @return integer
     */
    public function getWillpower() {
        return $this->willpower;
    }

    /**
     * Set attack
     *
     * @param integer $attack
     *
     * @return CardPrinting
     */
    public function setAttack($attack) {
        $this->attack = $attack;

        return $this;
    }

    /**
     * Get attack
     *
     * @return integer
     */
    public function getAttack() {
        return $this->attack;
    }

    /**
     * Set defense
     *
     * @param integer $defense
     *
     * @return CardPrinting
     */
    public function setDefense($defense) {
        $this->defense = $defense;

        return $this;
    }

    /**
     * Get defense
     *
     * @return integer
     */
    public function getDefense() {
        return $this->defense;
    }

    /**
     * Set health
     *
     * @param integer $health
     *
     * @return CardPrinting
     */
    public function setHealth($health) {
        $this->health = $health;

        return $this;
    }

    /**
     * Get health
     *
     * @return integer
     */
    public function getHealth() {
        return $this->health;
    }

    /**
     * Set victory
     *
     * @param integer $victory
     *
     * @return CardPrinting
     */
    public function setVictory($victory) {
        $this->victory = $victory;

        return $this;
    }

    /**
     * Get victory
     *
     * @return integer
     */
    public function getVictory() {
        return $this->victory;
    }

    /**
     * Set quest
     *
     * @param integer $quest
     *
     * @return CardPrinting
     */
    public function setQuest($quest) {
        $this->quest = $quest;

        return $this;
    }

    /**
     * Get quest
     *
     * @return integer
     */
    public function getQuest() {
        return $this->quest;
    }

    /**
     * Set dateCreation
     *
     * @param \DateTime $dateCreation
     *
     * @return CardPrinting
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
     * @return CardPrinting
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
     * Set card
     *
     * @param \AppBundle\Entity\Card $card
     *
     * @return CardPrinting
     */
    public function setCard(\AppBundle\Entity\Card $card = null) {
        $this->card = $card;

        return $this;
    }

    /**
     * Get card
     *
     * @return \AppBundle\Entity\Card
     */
    public function getCard() {
        return $this->card;
    }

    /**
     * Set pack
     *
     * @param \AppBundle\Entity\Pack $pack
     *
     * @return CardPrinting
     */
    public function setPack(\AppBundle\Entity\Pack $pack = null) {
        $this->pack = $pack;

        return $this;
    }

    /**
     * Get pack
     *
     * @return \AppBundle\Entity\Pack
     */
    public function getPack() {
        return $this->pack;
    }
}
