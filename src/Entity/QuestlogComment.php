<?php

namespace App\Entity;

/**
 * QuestlogComment
 */
class QuestlogComment {
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
     * @var \App\Entity\Questlog
     */
    private $questlog;

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
     * @return QuestlogComment
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
     * @return QuestlogComment
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
     * @return QuestlogComment
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
     * @return QuestlogComment
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
     * Set questlog
     *
     * @param \App\Entity\Questlog $questlog
     *
     * @return QuestlogComment
     */
    public function setQuestlog(\App\Entity\Questlog $questlog) {
        $this->questlog = $questlog;

        return $this;
    }

    /**
     * Get questlog
     *
     * @return \App\Entity\Questlog
     */
    public function getQuestlog() {
        return $this->questlog;
    }
}

