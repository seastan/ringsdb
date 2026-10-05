<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

class Card
{
    /**
     * @var int|null
     */
    private $id;

    /**
     * @var int|null
     */
    private $position;

    /**
     * @var string|null
     */
    private $code;

    /**
     * @var string|null
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
    private $isUnique = false;

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
     * @var Collection<int, Review>
     */
    private $reviews;

    /**
     * @var Collection<int, CardPrinting>
     */
    private $printings;

    /**
     * @var Type|null
     */
    private $type;

    /**
     * @var Sphere|null
     */
    private $sphere;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->reviews = new ArrayCollection();
        $this->printings = new ArrayCollection();
        $this->dateCreation = new \DateTime();
        $this->dateUpdate = new \DateTime();
    }

    /**
     * Add printing.
     */
    public function addPrinting(CardPrinting $printing): Card
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
     * @return Collection<int, CardPrinting>
     */
    public function getPrintings(): Collection
    {
        return $this->printings;
    }

    public function getPrimaryPrinting(): ?CardPrinting
    {
        $primary = null;
        foreach ($this->printings as $p) {
            if (null === $primary) {
                $primary = $p;
                continue;
            }

            // a printing always has a pack (card_printing.pack_id is NOT NULL)
            // except for a Card that has not been persisted yet
            $pDate = $p->getPack() ? $p->getPack()->getDateRelease() : null;
            $primaryDate = $primary->getPack() ? $primary->getPack()->getDateRelease() : null;

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
    public function setPosition($position): Card
    {
        $this->position = $position;

        return $this;
    }

    /**
     * Get position.
     */
    public function getPosition(): ?int
    {
        return $this->position;
    }

    /**
     * Set code.
     *
     * @param string $code
     */
    public function setCode($code): Card
    {
        $this->code = $code;

        return $this;
    }

    /**
     * Get code.
     */
    public function getCode(): ?string
    {
        return $this->code;
    }

    /**
     * Set name.
     *
     * @param string $name
     */
    public function setName($name): Card
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name.
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    public function getAdminLabel(): string
    {
        return sprintf(
            '%s (%s, %s)',
            $this->getName() ?? '<name>',
            $this->getSphere() instanceof Sphere ? $this->getSphere()->getName() : '<sphere>',
            $this->getType() instanceof Type ? $this->getType()->getName() : '<type>',
        );
    }

    /**
     * Set traits.
     *
     * @param string|null $traits
     */
    public function setTraits($traits): Card
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
    public function setText($text): Card
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
     * Set flavor.
     *
     * @param string|null $flavor
     */
    public function setFlavor($flavor): Card
    {
        $this->flavor = $flavor;

        return $this;
    }

    /**
     * Get flavor.
     */
    public function getFlavor(): ?string
    {
        return $this->flavor;
    }

    /**
     * Set isUnique.
     *
     * @param bool $isUnique
     */
    public function setIsUnique($isUnique): Card
    {
        $this->isUnique = $isUnique;

        return $this;
    }

    /**
     * Get isUnique.
     */
    public function getIsUnique(): bool
    {
        return $this->isUnique;
    }

    /**
     * Set cost.
     *
     * @param string|null $cost
     */
    public function setCost($cost): Card
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
    public function setThreat($threat): Card
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
    public function setWillpower($willpower): Card
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
    public function setAttack($attack): Card
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
    public function setDefense($defense): Card
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
    public function setHealth($health): Card
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
    public function setVictory($victory): Card
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

    public function getQuantity(): ?int
    {
        $p = $this->getPrimaryPrinting();

        return $p instanceof CardPrinting ? $p->getQuantity() : null;
    }

    /**
     * Set deckLimit.
     *
     * @param int|null $deckLimit null (an empty field of the admin form or of a CSV import) for
     *                            the default, 3
     */
    public function setDeckLimit($deckLimit): Card
    {
        $this->deckLimit = $deckLimit ?? 3;

        return $this;
    }

    /**
     * Get deckLimit.
     */
    public function getDeckLimit(): int
    {
        return $this->deckLimit;
    }

    public function getIllustrator(): ?string
    {
        $p = $this->getPrimaryPrinting();

        return $p instanceof CardPrinting ? $p->getIllustrator() : null;
    }

    public function getOctgnid(): ?string
    {
        $p = $this->getPrimaryPrinting();

        return $p instanceof CardPrinting ? $p->getOctgnid() : null;
    }

    /**
     * Set dateCreation.
     *
     * @param \DateTime $dateCreation
     */
    public function setDateCreation($dateCreation): Card
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
    public function setDateUpdate($dateUpdate): Card
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
     * Add review.
     */
    public function addReview(Review $review): Card
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
     * @return Collection<int, Review>
     */
    public function getReviews(): Collection
    {
        return $this->reviews;
    }

    public function getPack(): ?Pack
    {
        $p = $this->getPrimaryPrinting();

        return $p instanceof CardPrinting ? $p->getPack() : null;
    }

    /**
     * Set type.
     */
    public function setType(Type $type): Card
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get type.
     */
    public function getType(): ?Type
    {
        return $this->type;
    }

    /**
     * Set sphere.
     */
    public function setSphere(Sphere $sphere): Card
    {
        $this->sphere = $sphere;

        return $this;
    }

    /**
     * Get sphere.
     */
    public function getSphere(): ?Sphere
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
     */
    public function setQuest($quest): Card
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
     * @var bool
     */
    private $hasErrata = false;

    /**
     * Set hasErrata.
     *
     * @param bool $hasErrata
     */
    public function setHasErrata($hasErrata): Card
    {
        $this->hasErrata = $hasErrata;

        return $this;
    }

    /**
     * Get hasErrata.
     */
    public function getHasErrata(): bool
    {
        return $this->hasErrata;
    }
}
