<?php

namespace AppBundle\Entity;

/**
 * Scenario
 */
class Scenario implements \JsonSerializable {
    public function jsonSerialize() {
        $encounters = $this->getEncounters()->toArray();
        $pack = $this->getPack();

        $array = [
            'id' => $this->getId(),
            'code' => $this->getCode(),
            'name' => $this->getName(),
            'pack' => $pack ? $pack->getName() : '',
            'date_creation' => $this->getDateCreation()->format('c'),
            'date_update' => $this->getDateUpdate()->format('c'),
            'encounters' => $encounters
        ];

        return $array;
    }


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
     * @var \DateTime
     */
    private $dateCreation;
    /**
     * @var \DateTime
     */
    private $dateUpdate;
    /**
     * @var \AppBundle\Entity\Pack
     */
    private $pack;
    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $encounters;

    /**
     * Constructor
     */
    public function __construct() {
        $this->encounters = new \Doctrine\Common\Collections\ArrayCollection();
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
     * @return Scenario
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
     * @return Scenario
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
     * Set dateCreation
     *
     * @param \DateTime $dateCreation
     *
     * @return Scenario
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
     * @return Scenario
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
     * Set pack
     *
     * @param \AppBundle\Entity\Pack $pack
     *
     * @return Scenario
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

    /**
     * Add encounter
     *
     * @param \AppBundle\Entity\Encounter $encounter
     *
     * @return Scenario
     */
    public function addEncounter(\AppBundle\Entity\Encounter $encounter) {
        $this->encounters[] = $encounter;

        return $this;
    }

    /**
     * Remove encounter
     *
     * @param \AppBundle\Entity\Encounter $encounter
     */
    public function removeEncounter(\AppBundle\Entity\Encounter $encounter) {
        $this->encounters->removeElement($encounter);
    }

    /**
     * Get encounters
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getEncounters() {
        return $this->encounters;
    }

    /**
     * @var integer
     */
    private $position;

    /**
     * Set position
     *
     * @param integer $position
     *
     * @return Scenario
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
}
