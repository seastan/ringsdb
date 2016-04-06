<?php

namespace AppBundle\Entity;

/**
 * FellowshipComment
 */
class FellowshipComment {
    /**
     * @var integer
     */
    private $id;
    /**
     * @var \DateTime
     */
    private $dateCreation;
    /**
     * @var \DateTime
     */
    private $dateUpdate;
    /**
     * @var string
     */
    private $text;
    /**
     * @var \AppBundle\Entity\User
     */
    private $user;
    /**
     * @var \AppBundle\Entity\Fellowship
     */
    private $fellowship;

    /**
     * Get id
     *
     * @return integer
     */
    public function getId() {
        return $this->id;
    }

    /**
     * Set dateCreation
     *
     * @param \DateTime $dateCreation
     *
     * @return FellowshipComment
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
     * @return FellowshipComment
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
     * Set text
     *
     * @param string $text
     *
     * @return FellowshipComment
     */
    public function setText($text) {
        $this->text = $text;

        return $this;
    }

    /**
     * Get text
     *
     * @return string
     */
    public function getText() {
        return $this->text;
    }

    /**
     * Set user
     *
     * @param \AppBundle\Entity\User $user
     *
     * @return FellowshipComment
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
     * Set fellowship
     *
     * @param \AppBundle\Entity\Fellowship $fellowship
     *
     * @return FellowshipComment
     */
    public function setFellowship(\AppBundle\Entity\Fellowship $fellowship = null) {
        $this->fellowship = $fellowship;

        return $this;
    }

    /**
     * Get fellowship
     *
     * @return \AppBundle\Entity\Fellowship
     */
    public function getFellowship() {
        return $this->fellowship;
    }

    /**
     * @var boolean
     */
    private $isHidden;

    /**
     * Set isHidden
     *
     * @param boolean $isHidden
     *
     * @return FellowshipComment
     */
    public function setIsHidden($isHidden) {
        $this->isHidden = $isHidden;

        return $this;
    }

    /**
     * Get isHidden
     *
     * @return boolean
     */
    public function getIsHidden() {
        return $this->isHidden;
    }
}
