<?php

declare(strict_types=1);

namespace App\Entity;

/**
 * Encounter.
 */
class Encounter implements \JsonSerializable
{
    public function jsonSerialize()
    {
        return [
            'id' => $this->getId(),
            'code' => $this->getCode(),
            'name' => $this->getName(),
        ];
    }

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
     * @var \DateTime
     */
    private $dateCreation;

    /**
     * @var \DateTime
     */
    private $dateUpdate;

    /**
     * @var Pack|null
     */
    private $pack;

    public function __toString()
    {
        return $this->getName();
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
    public function setCode($code): Encounter
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
    public function setName($name): Encounter
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
     * Set dateCreation.
     *
     * @param \DateTime $dateCreation
     */
    public function setDateCreation($dateCreation): Encounter
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
    public function setDateUpdate($dateUpdate): Encounter
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
     * Set pack.
     */
    public function setPack(?Pack $pack = null): Encounter
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
