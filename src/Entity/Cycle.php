<?php

namespace App\Entity;

class Cycle
{
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
     * @return Cycle
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
     * @return Cycle
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
     * Set position.
     *
     * @param int $position
     *
     * @return Cycle
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
     * Set isBox.
     *
     * @param bool $isBox
     *
     * @return Cycle
     */
    public function setIsBox($isBox)
    {
        $this->isBox = $isBox;

        return $this;
    }

    /**
     * Get isBox.
     *
     * @return bool
     */
    public function getIsBox()
    {
        return $this->isBox;
    }

    /**
     * Set isSaga.
     *
     * @param bool $isSaga
     *
     * @return Cycle
     */
    public function setIsSaga($isSaga)
    {
        $this->isSaga = $isSaga;

        return $this;
    }

    /**
     * Get isSaga.
     *
     * @return bool
     */
    public function getIsSaga()
    {
        return $this->isSaga;
    }

    /**
     * Set dateCreation.
     *
     * @param \DateTime $dateCreation
     *
     * @return Cycle
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
     * @return Cycle
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
     * Add pack.
     *
     * @return Cycle
     */
    public function addPack(Pack $pack)
    {
        $this->packs[] = $pack;

        return $this;
    }

    /**
     * Remove pack.
     *
     * @return void
     */
    public function removePack(Pack $pack)
    {
        $this->packs->removeElement($pack);
    }

    /**
     * Get packs.
     *
     * @return \Doctrine\Common\Collections\Collection<int, Pack>
     */
    public function getPacks()
    {
        return $this->packs;
    }
}
