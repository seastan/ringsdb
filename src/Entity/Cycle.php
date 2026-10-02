<?php

declare(strict_types=1);

namespace App\Entity;

class Cycle
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
     * @var bool
     */
    private $isBox;
    /**
     * @var bool
     */
    private $isSaga;
    /**
     * @var \DateTime
     */
    private $dateCreation;
    /**
     * @var \DateTime
     */
    private $dateUpdate;
    /**
     * @var \Doctrine\Common\Collections\Collection<int, Pack>
     */
    private $packs;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->packs = new \Doctrine\Common\Collections\ArrayCollection();
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
    public function setCode($code): Cycle
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
    public function setName($name): Cycle
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
    public function setPosition($position): Cycle
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
     * Set isBox.
     *
     * @param bool $isBox
     */
    public function setIsBox($isBox): Cycle
    {
        $this->isBox = $isBox;

        return $this;
    }

    /**
     * Get isBox.
     */
    public function getIsBox(): bool
    {
        return $this->isBox;
    }

    /**
     * Set isSaga.
     *
     * @param bool $isSaga
     */
    public function setIsSaga($isSaga): Cycle
    {
        $this->isSaga = $isSaga;

        return $this;
    }

    /**
     * Get isSaga.
     */
    public function getIsSaga(): bool
    {
        return $this->isSaga;
    }

    /**
     * Set dateCreation.
     *
     * @param \DateTime $dateCreation
     */
    public function setDateCreation($dateCreation): Cycle
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
    public function setDateUpdate($dateUpdate): Cycle
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
     * Add pack.
     */
    public function addPack(Pack $pack): Cycle
    {
        $this->packs[] = $pack;

        return $this;
    }

    /**
     * Remove pack.
     */
    public function removePack(Pack $pack): void
    {
        $this->packs->removeElement($pack);
    }

    /**
     * Get packs.
     *
     * @return \Doctrine\Common\Collections\Collection<int, Pack>
     */
    public function getPacks(): \Doctrine\Common\Collections\Collection
    {
        return $this->packs;
    }
}
