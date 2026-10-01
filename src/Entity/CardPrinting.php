<?php

namespace App\Entity;

class CardPrinting
{
    /**
     * @var int
     */
    private $id;
    /**
     * @var int
     */
    private $position;
    /**
     * @var int
     */
    private $quantity;
    /**
     * @var string|null
     */
    private $illustrator;
    /**
     * @var string|null
     */
    private $octgnid;
    /**
     * @var string
     */
    private $imageCode;
    /**
     * @var string|null
     */
    private $traits;
    /**
     * @var string|null
     */
    private $text;
    /**
     * @var string|null
     */
    private $cost;
    /**
     * @var int|null
     */
    private $threat;
    /**
     * @var int|null
     */
    private $willpower;
    /**
     * @var int|null
     */
    private $attack;
    /**
     * @var int|null
     */
    private $defense;
    /**
     * @var int|null
     */
    private $health;
    /**
     * @var int|null
     */
    private $victory;
    /**
     * @var int|null
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
     * @var Card
     */
    private $card;
    /**
     * @var Pack
     */
    private $pack;

    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set position.
     *
     * @param int $position
     *
     * @return CardPrinting
     */
    public function setPosition($position)
    {
        $this->position = $position;

        return $this;
    }

    /**
     * Get position.
     *
     * @return int
     */
    public function getPosition()
    {
        return $this->position;
    }

    /**
     * Set quantity.
     *
     * @param int $quantity
     *
     * @return CardPrinting
     */
    public function setQuantity($quantity)
    {
        $this->quantity = $quantity;

        return $this;
    }

    /**
     * Get quantity.
     *
     * @return int
     */
    public function getQuantity()
    {
        return $this->quantity;
    }

    /**
     * Set illustrator.
     *
     * @param string|null $illustrator
     *
     * @return CardPrinting
     */
    public function setIllustrator($illustrator)
    {
        $this->illustrator = $illustrator;

        return $this;
    }

    /**
     * Get illustrator.
     *
     * @return string|null
     */
    public function getIllustrator()
    {
        return $this->illustrator;
    }

    /**
     * Set octgnid.
     *
     * @param string|null $octgnid
     *
     * @return CardPrinting
     */
    public function setOctgnid($octgnid)
    {
        $this->octgnid = $octgnid;

        return $this;
    }

    /**
     * Get octgnid.
     *
     * @return string|null
     */
    public function getOctgnid()
    {
        return $this->octgnid;
    }

    /**
     * Set imageCode.
     *
     * @param string $imageCode
     *
     * @return CardPrinting
     */
    public function setImageCode($imageCode)
    {
        $this->imageCode = $imageCode;

        return $this;
    }

    /**
     * Get imageCode.
     *
     * @return string
     */
    public function getImageCode()
    {
        return $this->imageCode;
    }

    /**
     * Set traits.
     *
     * @param string|null $traits
     *
     * @return CardPrinting
     */
    public function setTraits($traits)
    {
        $this->traits = $traits;

        return $this;
    }

    /**
     * Get traits.
     *
     * @return string|null
     */
    public function getTraits()
    {
        return $this->traits;
    }

    /**
     * Set text.
     *
     * @param string|null $text
     *
     * @return CardPrinting
     */
    public function setText($text)
    {
        $this->text = $text;

        return $this;
    }

    /**
     * Get text.
     *
     * @return string|null
     */
    public function getText()
    {
        return $this->text;
    }

    /**
     * Set cost.
     *
     * @param string|null $cost
     *
     * @return CardPrinting
     */
    public function setCost($cost)
    {
        $this->cost = $cost;

        return $this;
    }

    /**
     * Get cost.
     *
     * @return string|null
     */
    public function getCost()
    {
        return $this->cost;
    }

    /**
     * Set threat.
     *
     * @param int|null $threat
     *
     * @return CardPrinting
     */
    public function setThreat($threat)
    {
        $this->threat = $threat;

        return $this;
    }

    /**
     * Get threat.
     *
     * @return int|null
     */
    public function getThreat()
    {
        return $this->threat;
    }

    /**
     * Set willpower.
     *
     * @param int|null $willpower
     *
     * @return CardPrinting
     */
    public function setWillpower($willpower)
    {
        $this->willpower = $willpower;

        return $this;
    }

    /**
     * Get willpower.
     *
     * @return int|null
     */
    public function getWillpower()
    {
        return $this->willpower;
    }

    /**
     * Set attack.
     *
     * @param int|null $attack
     *
     * @return CardPrinting
     */
    public function setAttack($attack)
    {
        $this->attack = $attack;

        return $this;
    }

    /**
     * Get attack.
     *
     * @return int|null
     */
    public function getAttack()
    {
        return $this->attack;
    }

    /**
     * Set defense.
     *
     * @param int|null $defense
     *
     * @return CardPrinting
     */
    public function setDefense($defense)
    {
        $this->defense = $defense;

        return $this;
    }

    /**
     * Get defense.
     *
     * @return int|null
     */
    public function getDefense()
    {
        return $this->defense;
    }

    /**
     * Set health.
     *
     * @param int|null $health
     *
     * @return CardPrinting
     */
    public function setHealth($health)
    {
        $this->health = $health;

        return $this;
    }

    /**
     * Get health.
     *
     * @return int|null
     */
    public function getHealth()
    {
        return $this->health;
    }

    /**
     * Set victory.
     *
     * @param int|null $victory
     *
     * @return CardPrinting
     */
    public function setVictory($victory)
    {
        $this->victory = $victory;

        return $this;
    }

    /**
     * Get victory.
     *
     * @return int|null
     */
    public function getVictory()
    {
        return $this->victory;
    }

    /**
     * Set quest.
     *
     * @param int|null $quest
     *
     * @return CardPrinting
     */
    public function setQuest($quest)
    {
        $this->quest = $quest;

        return $this;
    }

    /**
     * Get quest.
     *
     * @return int|null
     */
    public function getQuest()
    {
        return $this->quest;
    }

    /**
     * Set dateCreation.
     *
     * @param \DateTime $dateCreation
     *
     * @return CardPrinting
     */
    public function setDateCreation($dateCreation)
    {
        $this->dateCreation = $dateCreation;

        return $this;
    }

    /**
     * Get dateCreation.
     *
     * @return \DateTime
     */
    public function getDateCreation()
    {
        return $this->dateCreation;
    }

    /**
     * Set dateUpdate.
     *
     * @param \DateTime $dateUpdate
     *
     * @return CardPrinting
     */
    public function setDateUpdate($dateUpdate)
    {
        $this->dateUpdate = $dateUpdate;

        return $this;
    }

    /**
     * Get dateUpdate.
     *
     * @return \DateTime
     */
    public function getDateUpdate()
    {
        return $this->dateUpdate;
    }

    /**
     * Set card.
     *
     * @return CardPrinting
     */
    public function setCard(Card $card)
    {
        $this->card = $card;

        return $this;
    }

    /**
     * Get card.
     *
     * @return Card
     */
    public function getCard()
    {
        return $this->card;
    }

    /**
     * Set pack.
     *
     * @return CardPrinting
     */
    public function setPack(Pack $pack)
    {
        $this->pack = $pack;

        return $this;
    }

    /**
     * Get pack.
     *
     * @return Pack
     */
    public function getPack()
    {
        return $this->pack;
    }
}
