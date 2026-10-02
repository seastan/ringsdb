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
        $array = [
            'id' => $this->getId(),
            'code' => $this->getCode(),
            'name' => $this->getName(),
        ];

        return $array;
    }

    /**
     * @var int
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

    /**
     * Constructor.
     */
    public function __construct()
    {
    }

    public function __toString()
    {
        return (string) $this->getName();
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
     * Set code.
     *
     * @param string $code
     *
     * @return Encounter
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
     * @return Encounter
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
     * Set dateCreation.
     *
     * @param \DateTime $dateCreation
     *
     * @return Encounter
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
     * @return Encounter
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
     * Set pack.
     *
     * @return Encounter
     */
    public function setPack(?Pack $pack = null)
    {
        $this->pack = $pack;

        return $this;
    }

    /**
     * Get pack.
     *
     * @return Pack|null
     */
    public function getPack()
    {
        return $this->pack;
    }
}
