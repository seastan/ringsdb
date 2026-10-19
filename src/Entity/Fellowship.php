<?php

namespace App\Entity;

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
    private $nameCanonical;
    /**
     * @var string|null
     */
    private $descriptionMd;
    /**
     * @var string|null
     */
    private $descriptionHtml;
    /**
     * @var boolean
     */
    private $isPublic;
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
    private $dateLastComment;
    /**
     * @var \Doctrine\Common\Collections\Collection<int, \App\Entity\FellowshipDeck>
     */
    private $decks;
    /**
     * @var \Doctrine\Common\Collections\Collection<int, \App\Entity\FellowshipDecklist>
     */
    private $decklists;
    /**
     * @var \Doctrine\Common\Collections\Collection<int, \App\Entity\FellowshipComment>
     */
    private $comments;
    /**
     * @var \App\Entity\User
     */
    private $user;
    /**
     * @var \Doctrine\Common\Collections\Collection<int, \App\Entity\User>
     */
    private $favorites;
    /**
     * @var \Doctrine\Common\Collections\Collection<int, \App\Entity\User>
     */
    private $votes;

    /**
     * Constructor
     */
    public function __construct() {
        $this->decks = new \Doctrine\Common\Collections\ArrayCollection();
        $this->decklists = new \Doctrine\Common\Collections\ArrayCollection();
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
     * Set nameCanonical
     *
     * @param string $nameCanonical
     *
     * @return Fellowship
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
     * @param string|null $descriptionMd
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
     * @return string|null
     */
    public function getDescriptionMd() {
        return $this->descriptionMd;
    }

    /**
     * Set descriptionHtml
     *
     * @param string|null $descriptionHtml
     *
     * @return Fellowship
     */
    public function setDescriptionHtml($descriptionHtml) {
        $this->descriptionHtml = $descriptionHtml;

        return $this;
    }

    /**
     * Get descriptionHtml
     *
     * @return string|null
     */
    public function getDescriptionHtml() {
        return $this->descriptionHtml;
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

    /**
     * Set nbVotes
     *
     * @param integer $nbVotes
     *
     * @return Fellowship
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
     * @return Fellowship
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
     * @return Fellowship
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
     * Set dateLastComment
     *
     * @param \DateTime|null $dateLastComment
     *
     * @return Fellowship
     */
    public function setDateLastComment($dateLastComment) {
        $this->dateLastComment = $dateLastComment;

        return $this;
    }

    /**
     * Get dateLastComment
     *
     * @return \DateTime|null
     */
    public function getDateLastComment() {
        return $this->dateLastComment;
    }

    /**
     * Add deck
     *
     * @param \App\Entity\FellowshipDeck $deck
     *
     * @return Fellowship
     */
    public function addDeck(\App\Entity\FellowshipDeck $deck) {
        $this->decks[] = $deck;

        return $this;
    }

    /**
     * Remove deck
     *
     * @param \App\Entity\FellowshipDeck $deck
     * @return void
     */
    public function removeDeck(\App\Entity\FellowshipDeck $deck) {
        $this->decks->removeElement($deck);
    }

    /**
     * Get decks
     *
     * @return \Doctrine\Common\Collections\Collection<int, \App\Entity\FellowshipDeck>
     */
    public function getDecks() {
        return $this->decks;
    }

    /**
     * Add decklist
     *
     * @param \App\Entity\FellowshipDecklist $decklist
     *
     * @return Fellowship
     */
    public function addDecklist(\App\Entity\FellowshipDecklist $decklist) {
        $this->decklists[] = $decklist;

        return $this;
    }

    /**
     * Remove decklist
     *
     * @param \App\Entity\FellowshipDecklist $decklist
     * @return void
     */
    public function removeDecklist(\App\Entity\FellowshipDecklist $decklist) {
        $this->decklists->removeElement($decklist);
    }

    /**
     * Get decklists
     *
     * @return \Doctrine\Common\Collections\Collection<int, \App\Entity\FellowshipDecklist>
     */
    public function getDecklists() {
        return $this->decklists;
    }

    /**
     * Add comment
     *
     * @param \App\Entity\FellowshipComment $comment
     *
     * @return Fellowship
     */
    public function addComment(\App\Entity\FellowshipComment $comment) {
        $this->comments[] = $comment;

        return $this;
    }

    /**
     * Remove comment
     *
     * @param \App\Entity\FellowshipComment $comment
     * @return void
     */
    public function removeComment(\App\Entity\FellowshipComment $comment) {
        $this->comments->removeElement($comment);
    }

    /**
     * Get comments
     *
     * @return \Doctrine\Common\Collections\Collection<int, \App\Entity\FellowshipComment>
     */
    public function getComments() {
        return $this->comments;
    }

    /**
     * Set user
     *
     * @param \App\Entity\User $user
     *
     * @return Fellowship
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
     * Add favorite
     *
     * @param \App\Entity\User $favorite
     *
     * @return Fellowship
     */
    public function addFavorite(\App\Entity\User $favorite) {
        $this->favorites[] = $favorite;

        return $this;
    }

    /**
     * Remove favorite
     *
     * @param \App\Entity\User $favorite
     * @return void
     */
    public function removeFavorite(\App\Entity\User $favorite) {
        $this->favorites->removeElement($favorite);
    }

    /**
     * Get favorites
     *
     * @return \Doctrine\Common\Collections\Collection<int, \App\Entity\User>
     */
    public function getFavorites() {
        return $this->favorites;
    }

    /**
     * Add vote
     *
     * @param \App\Entity\User $vote
     *
     * @return Fellowship
     */
    public function addVote(\App\Entity\User $vote) {
        $this->votes[] = $vote;

        return $this;
    }

    /**
     * Remove vote
     *
     * @param \App\Entity\User $vote
     * @return void
     */
    public function removeVote(\App\Entity\User $vote) {
        $this->votes->removeElement($vote);
    }

    /**
     * Get votes
     *
     * @return \Doctrine\Common\Collections\Collection<int, \App\Entity\User>
     */
    public function getVotes() {
        return $this->votes;
    }

    /**
     * @var integer
     */
    private $nbDecks;

    /**
     * Set nbDecks
     *
     * @param integer $nbDecks
     *
     * @return Fellowship
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
     * @var \DateTime|null
     */
    private $datePublish;

    /**
     * Set datePublish
     *
     * @param \DateTime|null $datePublish
     *
     * @return Fellowship
     */
    public function setDatePublish($datePublish) {
        $this->datePublish = $datePublish;

        return $this;
    }

    /**
     * Get datePublish
     *
     * @return \DateTime|null
     */
    public function getDatePublish() {
        return $this->datePublish;
    }
}
