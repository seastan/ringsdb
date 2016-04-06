<?php

namespace AppBundle\Entity;

/**
 * Questlog
 */
class Questlog {
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
    private $nameCanonical;
    /**
     * @var string
     */
    private $descriptionMd;
    /**
     * @var string
     */
    private $descriptionHtml;
    /**
     * @var \DateTime
     */
    private $datePlayed;
    /**
     * @var string
     */
    private $questMode;
    /**
     * @var boolean
     */
    private $success;
    /**
     * @var integer
     */
    private $score;
    /**
     * @var integer
     */
    private $nbDecks;
    /**
     * @var integer
     */
    private $nbVotes;
    /**
     * @var integer
     */
    private $nbFavorites;
    /**
     * @var integer
     */
    private $nbComments;
    /**
     * @var boolean
     */
    private $isPublic;
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
    private $comments;
    /**
     * @var \AppBundle\Entity\User
     */
    private $user;
    /**
     * @var \AppBundle\Entity\Scenario
     */
    private $scenario;
    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $favorites;
    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $votes;

    /**
     * Constructor
     */
    public function __construct() {
        $this->decks = new \Doctrine\Common\Collections\ArrayCollection();
        $this->comments = new \Doctrine\Common\Collections\ArrayCollection();
        $this->favorites = new \Doctrine\Common\Collections\ArrayCollection();
        $this->votes = new \Doctrine\Common\Collections\ArrayCollection();
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
     * @return Questlog
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
     * Set nameCanonical
     *
     * @param string $nameCanonical
     *
     * @return Questlog
     */
    public function setNameCanonical($nameCanonical) {
        $this->nameCanonical = $nameCanonical;

        return $this;
    }

    /**
     * Get nameCanonical
     *
     * @return string
     */
    public function getNameCanonical() {
        return $this->nameCanonical;
    }

    /**
     * Set descriptionMd
     *
     * @param string $descriptionMd
     *
     * @return Questlog
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
     * Set descriptionHtml
     *
     * @param string $descriptionHtml
     *
     * @return Questlog
     */
    public function setDescriptionHtml($descriptionHtml) {
        $this->descriptionHtml = $descriptionHtml;

        return $this;
    }

    /**
     * Get descriptionHtml
     *
     * @return string
     */
    public function getDescriptionHtml() {
        return $this->descriptionHtml;
    }

    /**
     * Set datePlayed
     *
     * @param \DateTime $datePlayed
     *
     * @return Questlog
     */
    public function setDatePlayed($datePlayed) {
        $this->datePlayed = $datePlayed;

        return $this;
    }

    /**
     * Get datePlayed
     *
     * @return \DateTime
     */
    public function getDatePlayed() {
        return $this->datePlayed;
    }

    /**
     * Set questMode
     *
     * @param string $questMode
     *
     * @return Questlog
     */
    public function setQuestMode($questMode) {
        $this->questMode = $questMode;

        return $this;
    }

    /**
     * Get questMode
     *
     * @return string
     */
    public function getQuestMode() {
        return $this->questMode;
    }

    /**
     * Set success
     *
     * @param boolean $success
     *
     * @return Questlog
     */
    public function setSuccess($success) {
        $this->success = $success;

        return $this;
    }

    /**
     * Get success
     *
     * @return boolean
     */
    public function getSuccess() {
        return $this->success;
    }

    /**
     * Set score
     *
     * @param integer $score
     *
     * @return Questlog
     */
    public function setScore($score) {
        $this->score = $score;

        return $this;
    }

    /**
     * Get score
     *
     * @return integer
     */
    public function getScore() {
        return $this->score;
    }

    /**
     * Set nbDecks
     *
     * @param integer $nbDecks
     *
     * @return Questlog
     */
    public function setNbDecks($nbDecks) {
        $this->nbDecks = $nbDecks;

        return $this;
    }

    /**
     * Get nbDecks
     *
     * @return integer
     */
    public function getNbDecks() {
        return $this->nbDecks;
    }

    /**
     * Set nbVotes
     *
     * @param integer $nbVotes
     *
     * @return Questlog
     */
    public function setNbVotes($nbVotes) {
        $this->nbVotes = $nbVotes;

        return $this;
    }

    /**
     * Get nbVotes
     *
     * @return integer
     */
    public function getNbVotes() {
        return $this->nbVotes;
    }

    /**
     * Set nbFavorites
     *
     * @param integer $nbFavorites
     *
     * @return Questlog
     */
    public function setNbFavorites($nbFavorites) {
        $this->nbFavorites = $nbFavorites;

        return $this;
    }

    /**
     * Get nbFavorites
     *
     * @return integer
     */
    public function getNbFavorites() {
        return $this->nbFavorites;
    }

    /**
     * Set nbComments
     *
     * @param integer $nbComments
     *
     * @return Questlog
     */
    public function setNbComments($nbComments) {
        $this->nbComments = $nbComments;

        return $this;
    }

    /**
     * Get nbComments
     *
     * @return integer
     */
    public function getNbComments() {
        return $this->nbComments;
    }

    /**
     * Set isPublic
     *
     * @param boolean $isPublic
     *
     * @return Questlog
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

    /**
     * Set dateCreation
     *
     * @param \DateTime $dateCreation
     *
     * @return Questlog
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
     * @return Questlog
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
     * @param \AppBundle\Entity\QuestlogDeck $deck
     *
     * @return Questlog
     */
    public function addDeck(\AppBundle\Entity\QuestlogDeck $deck) {
        $this->decks[] = $deck;

        return $this;
    }

    /**
     * Remove deck
     *
     * @param \AppBundle\Entity\QuestlogDeck $deck
     */
    public function removeDeck(\AppBundle\Entity\QuestlogDeck $deck) {
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
     * Add comment
     *
     * @param \AppBundle\Entity\QuestlogComment $comment
     *
     * @return Questlog
     */
    public function addComment(\AppBundle\Entity\QuestlogComment $comment) {
        $this->comments[] = $comment;

        return $this;
    }

    /**
     * Remove comment
     *
     * @param \AppBundle\Entity\QuestlogComment $comment
     */
    public function removeComment(\AppBundle\Entity\QuestlogComment $comment) {
        $this->comments->removeElement($comment);
    }

    /**
     * Get comments
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getComments() {
        return $this->comments;
    }

    /**
     * Set user
     *
     * @param \AppBundle\Entity\User $user
     *
     * @return Questlog
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
     * Set scenario
     *
     * @param \AppBundle\Entity\Scenario $scenario
     *
     * @return Questlog
     */
    public function setScenario(\AppBundle\Entity\Scenario $scenario = null) {
        $this->scenario = $scenario;

        return $this;
    }

    /**
     * Get scenario
     *
     * @return \AppBundle\Entity\Scenario
     */
    public function getScenario() {
        return $this->scenario;
    }

    /**
     * Add favorite
     *
     * @param \AppBundle\Entity\User $favorite
     *
     * @return Questlog
     */
    public function addFavorite(\AppBundle\Entity\User $favorite) {
        $this->favorites[] = $favorite;

        return $this;
    }

    /**
     * Remove favorite
     *
     * @param \AppBundle\Entity\User $favorite
     */
    public function removeFavorite(\AppBundle\Entity\User $favorite) {
        $this->favorites->removeElement($favorite);
    }

    /**
     * Get favorites
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getFavorites() {
        return $this->favorites;
    }

    /**
     * Add vote
     *
     * @param \AppBundle\Entity\User $vote
     *
     * @return Questlog
     */
    public function addVote(\AppBundle\Entity\User $vote) {
        $this->votes[] = $vote;

        return $this;
    }

    /**
     * Remove vote
     *
     * @param \AppBundle\Entity\User $vote
     */
    public function removeVote(\AppBundle\Entity\User $vote) {
        $this->votes->removeElement($vote);
    }

    /**
     * Get votes
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getVotes() {
        return $this->votes;
    }

    /**
     * @var \DateTime
     */
    private $datePublish;

    /**
     * Set datePublish
     *
     * @param \DateTime $datePublish
     *
     * @return Questlog
     */
    public function setDatePublish($datePublish) {
        $this->datePublish = $datePublish;

        return $this;
    }

    /**
     * Get datePublish
     *
     * @return \DateTime
     */
    public function getDatePublish() {
        return $this->datePublish;
    }
}
