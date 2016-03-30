<?php

namespace AppBundle\Entity;

/**
 * Fellowship
 */
class Fellowship {
    /**
     * @var integer
     */
    private $id;
    /**
     * @var string
     */
    private $name;
    /**
     * @var string
     */
    private $descriptionMd;
    /**
     * @var \DateTime
     */
    private $dateCreation;
    /**
     * @var \DateTime
     */
    private $dateUpdate;
    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $decks;
    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $decklists;
    /**
     * @var \AppBundle\Entity\User
     */
    private $user;
    /**
     * @var boolean
     */
    private $isPublic;


    /**
     * Constructor
     */
    public function __construct() {
        $this->decks = new \Doctrine\Common\Collections\ArrayCollection();
        $this->decklists = new \Doctrine\Common\Collections\ArrayCollection();
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
     * Set name
     *
     * @param string $name
     *
     * @return Fellowship
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
     * Set descriptionMd
     *
     * @param string $descriptionMd
     *
     * @return Fellowship
     */
    public function setDescriptionMd($descriptionMd) {
        $this->descriptionMd = $descriptionMd;

        return $this;
    }

    /**
     * Get descriptionMd
     *
     * @return string
     */
    public function getDescriptionMd() {
        return $this->descriptionMd;
    }

    /**
     * Set dateCreation
     *
     * @param \DateTime $dateCreation
     *
     * @return Fellowship
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
     * @return Fellowship
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
     * Add deck
     *
     * @param \AppBundle\Entity\FellowshipDeck $deck
     *
     * @return Fellowship
     */
    public function addDeck(\AppBundle\Entity\FellowshipDeck $deck) {
        $this->decks[] = $deck;

        return $this;
    }

    /**
     * Remove deck
     *
     * @param \AppBundle\Entity\FellowshipDeck $deck
     */
    public function removeDeck(\AppBundle\Entity\FellowshipDeck $deck) {
        $this->decks->removeElement($deck);
    }

    /**
     * Get decks
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getDecks() {
        return $this->decks;
    }

    /**
     * Add decklist
     *
     * @param \AppBundle\Entity\FellowshipDecklist $decklist
     *
     * @return Fellowship
     */
    public function addDecklist(\AppBundle\Entity\FellowshipDecklist $decklist) {
        $this->decklists[] = $decklist;

        return $this;
    }

    /**
     * Remove decklist
     *
     * @param \AppBundle\Entity\FellowshipDecklist $decklist
     */
    public function removeDecklist(\AppBundle\Entity\FellowshipDecklist $decklist) {
        $this->decklists->removeElement($decklist);
    }

    /**
     * Get decklists
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getDecklists() {
        return $this->decklists;
    }

    /**
     * Set user
     *
     * @param \AppBundle\Entity\User $user
     *
     * @return Fellowship
     */
    public function setUser(\AppBundle\Entity\User $user = null) {
        $this->user = $user;

        return $this;
    }

    /**
     * Get user
     *
     * @return \AppBundle\Entity\User
     */
    public function getUser() {
        return $this->user;
    }

    /**
     * Set isPublic
     *
     * @param boolean $isPublic
     *
     * @return Fellowship
     */
    public function setIsPublic($isPublic) {
        $this->isPublic = $isPublic;

        return $this;
    }

    /**
     * Get isPublic
     *
     * @return boolean
     */
    public function getIsPublic() {
        return $this->isPublic;
    }
}
