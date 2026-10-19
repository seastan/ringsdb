<?php

namespace App\Entity;

/**
 * Comment
 */
class Comment {
    /**
     * @var integer
     */
    private $id;
    /**
     * @var string
     */
    private $text;
    /**
     * @var \DateTime
     */
    private $dateCreation;
    /**
     * @var boolean
     */
    private $isHidden;
    /**
     * @var \App\Entity\User
     */
    private $user;
    /**
     * @var \App\Entity\Decklist
     */
    private $decklist;

    /**
     * Get id
     *
     * @return integer
     */
    public function getId() {
        return $this->id;
    }

    /**
     * Set text
     *
     * @param string $text
     *
     * @return Comment
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
     * Set dateCreation
     *
     * @param \DateTime $dateCreation
     *
     * @return Comment
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
     * Set isHidden
     *
     * @param boolean $isHidden
     *
     * @return Comment
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

    /**
     * Set user
     *
     * @param \App\Entity\User $user
     *
     * @return Comment
     */
    public function setUser(\App\Entity\User $user) {
        $this->user = $user;

        return $this;
    }

    /**
     * Get user
     *
     * @return \App\Entity\User
     */
    public function getUser() {
        return $this->user;
    }

    /**
     * Set decklist
     *
     * @param \App\Entity\Decklist $decklist
     *
     * @return Comment
     */
    public function setDecklist(\App\Entity\Decklist $decklist) {
        $this->decklist = $decklist;

        return $this;
    }

    /**
     * Get decklist
     *
     * @return \App\Entity\Decklist
     */
    public function getDecklist() {
        return $this->decklist;
    }
}
