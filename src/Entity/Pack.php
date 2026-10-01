<?php

namespace App\Entity;

class Pack
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
     * @var \Doctrine\Common\Collections\Collection<int, CardPrinting>
     */
    private $printings;
    /**
     * @var Cycle
     */
    private $cycle;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->printings = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Set isRepackaged.
     *
     * @param bool $isRepackaged
     *
     * @return Pack
     */
    public function setIsRepackaged($isRepackaged)
    {
        $this->isRepackaged = $isRepackaged;

        return $this;
    }

    /**
     * Get isRepackaged.
     *
     * @return bool
     */
    public function getIsRepackaged()
    {
        return $this->isRepackaged;
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
     * @return Pack
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
     * @return Pack
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
     * @return Pack
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
     * Set size.
     *
     * @param int $size
     *
     * @return Pack
     */
    public function setSize($size)
    {
        $this->size = $size;

        return $this;
    }

    /**
     * Get size.
     *
     * @return int
     */
    public function getSize()
    {
        return $this->size;
    }

    /**
     * Set dateCreation.
     *
     * @param \DateTime $dateCreation
     *
     * @return Pack
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
     * @return Pack
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
     * Set dateRelease.
     *
     * @param \DateTime|null $dateRelease
     *
     * @return Pack
     */
    public function setDateRelease($dateRelease)
    {
        $this->dateRelease = $dateRelease;

        return $this;
    }

    /**
     * Get dateRelease.
     *
     * @return \DateTime|null
     */
    public function getDateRelease()
    {
        return $this->dateRelease;
    }

    /**
     * @return \Doctrine\Common\Collections\Collection<int, Card>
     */
    public function getCards()
    {
        return $this->printings->map(function ($p) { return $p->getCard(); });
    }

    /**
     * Add printing.
     *
     * @return Pack
     */
    public function addPrinting(CardPrinting $printing)
    {
        $this->printings[] = $printing;

        return $this;
    }

    /**
     * Remove printing.
     *
     * @return void
     */
    public function removePrinting(CardPrinting $printing)
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

    /**
     * Set cycle.
     *
     * @return Pack
     */
    public function setCycle(Cycle $cycle)
    {
        $this->cycle = $cycle;

        return $this;
    }

    /**
     * Get cycle.
     *
     * @return Cycle
     */
    public function getCycle()
    {
        return $this->cycle;
    }
}
