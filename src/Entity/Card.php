<?php

declare(strict_types=1);

namespace App\Entity;

class Card
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
     * @var string
     */
    private $code;
    /**
     * @var string
     */
    private $name;
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
    private $flavor;
    /**
     * @var bool
     */
    private $isUnique;
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
     * @var int
     */
    private $deckLimit = 3;
    /**
     * @var \DateTime
     */
    private $dateCreation;
    /**
     * @var \DateTime
     */
    private $dateUpdate;
    /**
     * @var \Doctrine\Common\Collections\Collection<int, Review>
     */
    private $reviews;
    /**
     * @var \Doctrine\Common\Collections\Collection<int, CardPrinting>
     */
    private $printings;
    /**
     * @var Type
     */
    private $type;
    /**
     * @var Sphere
     */
    private $sphere;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->reviews = new \Doctrine\Common\Collections\ArrayCollection();
        $this->printings = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Add printing.
     *
     * @return Card
     */
    public function addPrinting(CardPrinting $printing)
    {
        $this->printings[] = $printing;

        return $this;
    }

    /**
     * Remove printing.
     */
    public function removePrinting(CardPrinting $printing): void
    {
        $this->printings->removeElement($printing);
    }

    /**
     * Get printings.
     *
     * @return \Doctrine\Common\Collections\Collection<int, CardPrinting>
     */
    public function getPrintings()
    {
        return $this->printings;
    }

    public function getPrimaryPrinting()
    {
        $primary = null;
        foreach ($this->printings as $p) {
            if (null === $primary) {
                $primary = $p;
                continue;
            }

            // a printing always has a pack (card_printing.pack_id is NOT NULL)
            $pDate = $p->getPack()->getDateRelease();
            $primaryDate = $primary->getPack()->getDateRelease();

            // Prefer the earliest-released pack so the canonical printing is the
            // original one (e.g. Core Set over a later reprint or starter). A
            // null release date (unreleased/spoiled pack) sorts last; ties fall
            // back to the lower position.
            if (null === $pDate && null === $primaryDate) {
                $better = $p->getPosition() < $primary->getPosition();
            } elseif (null === $pDate) {
                $better = false;
            } elseif (null === $primaryDate) {
                $better = true;
            } elseif ($pDate == $primaryDate) {
                $better = $p->getPosition() < $primary->getPosition();
            } else {
                $better = $pDate < $primaryDate;
            }

            if ($better) {
                $primary = $p;
            }
        }

        return $primary;
    }

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
     * @return Card
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
     * Set code.
     *
     * @param string $code
     *
     * @return Card
     */
    public function setCode($code)
    {
        $this->code = $code;

        return $this;
    }

    /**
     * Get code.
     *
     * @return string
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * Set name.
     *
     * @param string $name
     *
     * @return Card
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getAdminLabel()
    {
        return $this->name.' ('.$this->sphere->getName().', '.$this->type->getName().')';
    }

    /**
     * Set traits.
     *
     * @param string|null $traits
     *
     * @return Card
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
     * @return Card
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
     * Set flavor.
     *
     * @param string|null $flavor
     *
     * @return Card
     */
    public function setFlavor($flavor)
    {
        $this->flavor = $flavor;

        return $this;
    }

    /**
     * Get flavor.
     *
     * @return string|null
     */
    public function getFlavor()
    {
        return $this->flavor;
    }

    /**
     * Set isUnique.
     *
     * @param bool $isUnique
     *
     * @return Card
     */
    public function setIsUnique($isUnique)
    {
        $this->isUnique = $isUnique;

        return $this;
    }

    /**
     * Get isUnique.
     *
     * @return bool
     */
    public function getIsUnique()
    {
        return $this->isUnique;
    }

    /**
     * Set cost.
     *
     * @param string|null $cost
     *
     * @return Card
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
     * @return Card
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
     * @return Card
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
     * @return Card
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
     * @return Card
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
     * @return Card
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
     * @return Card
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

    public function getQuantity()
    {
        $p = $this->getPrimaryPrinting();

        return $p ? $p->getQuantity() : null;
    }

    /**
     * Set deckLimit.
     *
     * @param int|null $deckLimit null (an empty field of the admin form or of a CSV import) for
     *                            the default, 3
     *
     * @return Card
     */
    public function setDeckLimit($deckLimit)
    {
        $this->deckLimit = $deckLimit ?? 3;

        return $this;
    }

    /**
     * Get deckLimit.
     *
     * @return int
     */
    public function getDeckLimit()
    {
        return $this->deckLimit;
    }

    public function getIllustrator()
    {
        $p = $this->getPrimaryPrinting();

        return $p ? $p->getIllustrator() : null;
    }

    public function getOctgnid()
    {
        $p = $this->getPrimaryPrinting();

        return $p ? $p->getOctgnid() : null;
    }

    /**
     * Set dateCreation.
     *
     * @param \DateTime $dateCreation
     *
     * @return Card
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
     * @return Card
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
     * Add review.
     *
     * @return Card
     */
    public function addReview(Review $review)
    {
        $this->reviews[] = $review;

        return $this;
    }

    /**
     * Remove review.
     */
    public function removeReview(Review $review): void
    {
        $this->reviews->removeElement($review);
    }

    /**
     * Get reviews.
     *
     * @return \Doctrine\Common\Collections\Collection<int, Review>
     */
    public function getReviews()
    {
        return $this->reviews;
    }

    public function getPack()
    {
        $p = $this->getPrimaryPrinting();

        return $p ? $p->getPack() : null;
    }

    /**
     * Set type.
     *
     * @return Card
     */
    public function setType(Type $type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get type.
     *
     * @return Type
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set sphere.
     *
     * @return Card
     */
    public function setSphere(Sphere $sphere)
    {
        $this->sphere = $sphere;

        return $this;
    }

    /**
     * Get sphere.
     *
     * @return Sphere
     */
    public function getSphere()
    {
        return $this->sphere;
    }

    /**
     * @var int|null
     */
    private $quest;

    /**
     * Set quest.
     *
     * @param int|null $quest
     *
     * @return Card
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
     * @var bool
     */
    private $hasErrata;

    /**
     * Set hasErrata.
     *
     * @param bool $hasErrata
     *
     * @return Card
     */
    public function setHasErrata($hasErrata)
    {
        $this->hasErrata = $hasErrata;

        return $this;
    }

    /**
     * Get hasErrata.
     *
     * @return bool
     */
    public function getHasErrata()
    {
        return $this->hasErrata;
    }
}
