<?php

declare(strict_types=1);

namespace App\Entity;

class CardPrinting
{
    /**
     * @var int|null
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

    private ?\App\Entity\Card $card = null;

    private ?\App\Entity\Pack $pack = null;

    /**
     * Get id.
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Set position.
     *
     * @param int $position
     */
    public function setPosition($position): CardPrinting
    {
        $this->position = $position;

        return $this;
    }

    /**
     * Get position.
     */
    public function getPosition(): int
    {
        return $this->position;
    }

    /**
     * Set quantity.
     *
     * @param int $quantity
     */
    public function setQuantity($quantity): CardPrinting
    {
        $this->quantity = $quantity;

        return $this;
    }

    /**
     * Get quantity.
     */
    public function getQuantity(): int
    {
        return $this->quantity;
    }

    /**
     * Set illustrator.
     *
     * @param string|null $illustrator
     */
    public function setIllustrator($illustrator): CardPrinting
    {
        $this->illustrator = $illustrator;

        return $this;
    }

    /**
     * Get illustrator.
     */
    public function getIllustrator(): ?string
    {
        return $this->illustrator;
    }

    /**
     * Set octgnid.
     *
     * @param string|null $octgnid
     */
    public function setOctgnid($octgnid): CardPrinting
    {
        $this->octgnid = $octgnid;

        return $this;
    }

    /**
     * Get octgnid.
     */
    public function getOctgnid(): ?string
    {
        return $this->octgnid;
    }

    /**
     * Set imageCode.
     *
     * @param string $imageCode
     */
    public function setImageCode($imageCode): CardPrinting
    {
        $this->imageCode = $imageCode;

        return $this;
    }

    /**
     * Get imageCode.
     */
    public function getImageCode(): string
    {
        return $this->imageCode;
    }

    /**
     * Set traits.
     *
     * @param string|null $traits
     */
    public function setTraits($traits): CardPrinting
    {
        $this->traits = $traits;

        return $this;
    }

    /**
     * Get traits.
     */
    public function getTraits(): ?string
    {
        return $this->traits;
    }

    /**
     * Set text.
     *
     * @param string|null $text
     */
    public function setText($text): CardPrinting
    {
        $this->text = $text;

        return $this;
    }

    /**
     * Get text.
     */
    public function getText(): ?string
    {
        return $this->text;
    }

    /**
     * Set cost.
     *
     * @param string|null $cost
     */
    public function setCost($cost): CardPrinting
    {
        $this->cost = $cost;

        return $this;
    }

    /**
     * Get cost.
     */
    public function getCost(): ?string
    {
        return $this->cost;
    }

    /**
     * Set threat.
     *
     * @param int|null $threat
     */
    public function setThreat($threat): CardPrinting
    {
        $this->threat = $threat;

        return $this;
    }

    /**
     * Get threat.
     */
    public function getThreat(): ?int
    {
        return $this->threat;
    }

    /**
     * Set willpower.
     *
     * @param int|null $willpower
     */
    public function setWillpower($willpower): CardPrinting
    {
        $this->willpower = $willpower;

        return $this;
    }

    /**
     * Get willpower.
     */
    public function getWillpower(): ?int
    {
        return $this->willpower;
    }

    /**
     * Set attack.
     *
     * @param int|null $attack
     */
    public function setAttack($attack): CardPrinting
    {
        $this->attack = $attack;

        return $this;
    }

    /**
     * Get attack.
     */
    public function getAttack(): ?int
    {
        return $this->attack;
    }

    /**
     * Set defense.
     *
     * @param int|null $defense
     */
    public function setDefense($defense): CardPrinting
    {
        $this->defense = $defense;

        return $this;
    }

    /**
     * Get defense.
     */
    public function getDefense(): ?int
    {
        return $this->defense;
    }

    /**
     * Set health.
     *
     * @param int|null $health
     */
    public function setHealth($health): CardPrinting
    {
        $this->health = $health;

        return $this;
    }

    /**
     * Get health.
     */
    public function getHealth(): ?int
    {
        return $this->health;
    }

    /**
     * Set victory.
     *
     * @param int|null $victory
     */
    public function setVictory($victory): CardPrinting
    {
        $this->victory = $victory;

        return $this;
    }

    /**
     * Get victory.
     */
    public function getVictory(): ?int
    {
        return $this->victory;
    }

    /**
     * Set quest.
     *
     * @param int|null $quest
     */
    public function setQuest($quest): CardPrinting
    {
        $this->quest = $quest;

        return $this;
    }

    /**
     * Get quest.
     */
    public function getQuest(): ?int
    {
        return $this->quest;
    }

    /**
     * Set dateCreation.
     *
     * @param \DateTime $dateCreation
     */
    public function setDateCreation($dateCreation): CardPrinting
    {
        $this->dateCreation = $dateCreation;

        return $this;
    }

    /**
     * Get dateCreation.
     */
    public function getDateCreation(): \DateTime
    {
        return $this->dateCreation;
    }

    /**
     * Set dateUpdate.
     *
     * @param \DateTime $dateUpdate
     */
    public function setDateUpdate($dateUpdate): CardPrinting
    {
        $this->dateUpdate = $dateUpdate;

        return $this;
    }

    /**
     * Get dateUpdate.
     */
    public function getDateUpdate(): \DateTime
    {
        return $this->dateUpdate;
    }

    /**
     * Set card.
     */
    public function setCard(Card $card): CardPrinting
    {
        $this->card = $card;

        return $this;
    }

    /**
     * Get card.
     */
    public function getCard(): ?Card
    {
        return $this->card;
    }

    /**
     * Set pack.
     */
    public function setPack(Pack $pack): CardPrinting
    {
        $this->pack = $pack;

        return $this;
    }

    /**
     * Get pack.
     */
    public function getPack(): ?Pack
    {
        return $this->pack;
    }
}
