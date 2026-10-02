<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

class Pack
{
    /**
     * @var int|null
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
     * @var int
     */
    private $position;
    /**
     * @var int
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
     * @var bool
     */
    private $isRepackaged = false;
    /**
     * @var Collection<int, CardPrinting>
     */
    private $printings;
    /**
     * @var Cycle|null
     */
    private $cycle;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->printings = new ArrayCollection();
    }

    /**
     * Set isRepackaged.
     *
     * @param bool $isRepackaged
     */
    public function setIsRepackaged($isRepackaged): Pack
    {
        $this->isRepackaged = $isRepackaged;

        return $this;
    }

    /**
     * Get isRepackaged.
     */
    public function getIsRepackaged(): bool
    {
        return $this->isRepackaged;
    }

    /**
     * Get id.
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Set code.
     *
     * @param string $code
     */
    public function setCode($code): Pack
    {
        $this->code = $code;

        return $this;
    }

    /**
     * Get code.
     */
    public function getCode(): string
    {
        return $this->code;
    }

    /**
     * Set name.
     *
     * @param string $name
     */
    public function setName($name): Pack
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name.
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Set position.
     *
     * @param int $position
     */
    public function setPosition($position): Pack
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
     * Set size.
     *
     * @param int $size
     */
    public function setSize($size): Pack
    {
        $this->size = $size;

        return $this;
    }

    /**
     * Get size.
     */
    public function getSize(): int
    {
        return $this->size;
    }

    /**
     * Set dateCreation.
     *
     * @param \DateTime $dateCreation
     */
    public function setDateCreation($dateCreation): Pack
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
    public function setDateUpdate($dateUpdate): Pack
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
     * Set dateRelease.
     *
     * @param \DateTime|null $dateRelease
     */
    public function setDateRelease($dateRelease): Pack
    {
        $this->dateRelease = $dateRelease;

        return $this;
    }

    /**
     * Get dateRelease.
     */
    public function getDateRelease(): ?\DateTime
    {
        return $this->dateRelease;
    }

    /**
     * @return Collection<int, Card>
     */
    public function getCards(): Collection
    {
        return $this->printings->map(fn ($p) => $p->getCard());
    }

    /**
     * Add printing.
     */
    public function addPrinting(CardPrinting $printing): Pack
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

    /**
     * Set cycle.
     */
    public function setCycle(Cycle $cycle): Pack
    {
        $this->cycle = $cycle;

        return $this;
    }

    /**
     * Get cycle.
     */
    public function getCycle(): ?Cycle
    {
        return $this->cycle;
    }
}
