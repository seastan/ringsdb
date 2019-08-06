<?php

namespace AppBundle\Entity;

class Decklist extends \AppBundle\Model\ExportableDeck implements \JsonSerializable {
    public function jsonSerialize() {
        $array = parent::getArrayExport();
        $array['is_published'] = true;
        $array['nb_votes'] = $this->getNbVotes();
        $array['nb_favorites'] = $this->getNbFavorites();
        $array['nb_comments'] = $this->getNbComments();
        $array['starting_threat'] = $this->getStartingThreat();

        return $array;
    }

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
     * @var \DateTime
     */
    private $dateCreation;
    /**
     * @var \DateTime
     */
    private $dateUpdate;
    /**
     * @var \DateTime
     */
    private $dateLastComment;
    /**
     * @var string
     */
    private $descriptionMd;
    /**
     * @var string
     */
    private $descriptionHtml;
    /**
     * @var string
     */
    private $signature;
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
     * @var bool
     */
    private $freezeComments;
    /**
     * @var integer
     */
    private $version;
    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $slots;
    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $sideslots;
    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $comments;
    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $successors;
    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $children;
    /**
     * @var \AppBundle\Entity\User
     */
    private $user;
    /**
     * @var \AppBundle\Entity\Pack
     */
    private $lastPack;
    /**
     * @var \AppBundle\Entity\Deck
     */
    private $parent;
    /**
     * @var \AppBundle\Entity\Decklist
     */
    private $precedent;
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
        $this->slots = new \Doctrine\Common\Collections\ArrayCollection();
        $this->sideslots = new \Doctrine\Common\Collections\ArrayCollection();
        $this->comments = new \Doctrine\Common\Collections\ArrayCollection();
        $this->successors = new \Doctrine\Common\Collections\ArrayCollection();
        $this->children = new \Doctrine\Common\Collections\ArrayCollection();
        $this->favorites = new \Doctrine\Common\Collections\ArrayCollection();
        $this->votes = new \Doctrine\Common\Collections\ArrayCollection();
        $this->spheres = new \Doctrine\Common\Collections\ArrayCollection();
        $this->fellowships = new \Doctrine\Common\Collections\ArrayCollection();
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
     * @return Decklist
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
     * @return Decklist
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
     * Set dateCreation
     *
     * @param \DateTime $dateCreation
     *
     * @return Decklist
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
     * @return Decklist
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
     * @param \DateTime $dateLastComment
     *
     * @return Decklist
     */
    public function setDateLastComment($dateLastComment) {
        $this->dateLastComment = $dateLastComment;

        return $this;
    }

    /**
     * Get dateLastComment
     *
     * @return \DateTime
     */
    public function getDateLastComment() {
        return $this->dateLastComment;
    }

    /**
     * Set descriptionMd
     *
     * @param string $descriptionMd
     *
     * @return Decklist
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
     * @return Decklist
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
     * Set signature
     *
     * @param string $signature
     *
     * @return Decklist
     */
    public function setSignature($signature) {
        $this->signature = $signature;

        return $this;
    }

    /**
     * Get signature
     *
     * @return string
     */
    public function getSignature() {
        return $this->signature;
    }

    /**
     * Set nbVotes
     *
     * @param integer $nbVotes
     *
     * @return Decklist
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
     * @return Decklist
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
     * @return Decklist
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
     * Set freezeComments
     *
     * @param integer $freezeComments
     *
     * @return Decklist
     */
    public function setFreezeComments($freezeComments) {
        $this->freezeComments = $freezeComments;

        return $this;
    }

    /**
     * Get freezeComments
     *
     * @return integer
     */
    public function getFreezeComments() {
        return $this->freezeComments;
    }

    /**
     * Add slot
     *
     * @param \AppBundle\Entity\Decklistslot $slot
     *
     * @return Decklist
     */
    public function addSlot(\AppBundle\Entity\Decklistslot $slot) {
        $this->slots[] = $slot;

        return $this;
    }

    /**
     * Remove slot
     *
     * @param \AppBundle\Entity\Decklistslot $slot
     */
    public function removeSlot(\AppBundle\Entity\Decklistslot $slot) {
        $this->slots->removeElement($slot);
    }

    /**
     * Get slots
     *
     * @return \AppBundle\Model\SlotCollectionInterface
     */
    public function getSlots() {
        return new \AppBundle\Model\SlotCollectionDecorator($this->slots);
    }

    /**
     * Add sideslot
     *
     * @param \AppBundle\Entity\Decklistsideslot $slot
     *
     * @return Decklist
     */
    public function addSideslot(\AppBundle\Entity\Decklistsideslot $sideslots) {
        $this->sideslots[] = $sideslots;

        return $this;
    }

    /**
     * Remove sideslot
     *
     * @param \AppBundle\Entity\Decklistsideslot $slot
     */
    public function removeSideslot(\AppBundle\Entity\Decklistslot $sideslots) {
        $this->sideslots->removeElement($sideslots);
    }

    /**
     * Get slots
     *
     * @return \AppBundle\Model\SlotCollectionInterface
     */
    public function getSideslots() {
        return new \AppBundle\Model\SlotCollectionDecorator($this->sideslots);
    }

    /**
     * Add comment
     *
     * @param \AppBundle\Entity\Comment $comment
     *
     * @return Decklist
     */
    public function addComment(\AppBundle\Entity\Comment $comment) {
        $this->comments[] = $comment;

        return $this;
    }

    /**
     * Remove comment
     *
     * @param \AppBundle\Entity\Comment $comment
     */
    public function removeComment(\AppBundle\Entity\Comment $comment) {
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
     * Add successor
     *
     * @param \AppBundle\Entity\Decklist $successor
     *
     * @return Decklist
     */
    public function addSuccessor(\AppBundle\Entity\Decklist $successor) {
        $this->successors[] = $successor;

        return $this;
    }

    /**
     * Remove successor
     *
     * @param \AppBundle\Entity\Decklist $successor
     */
    public function removeSuccessor(\AppBundle\Entity\Decklist $successor) {
        $this->successors->removeElement($successor);
    }

    /**
     * Get successors
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getSuccessors() {
        return $this->successors;
    }

    /**
     * Add child
     *
     * @param \AppBundle\Entity\Deck $child
     *
     * @return Decklist
     */
    public function addChild(\AppBundle\Entity\Deck $child) {
        $this->children[] = $child;

        return $this;
    }

    /**
     * Remove child
     *
     * @param \AppBundle\Entity\Deck $child
     */
    public function removeChild(\AppBundle\Entity\Deck $child) {
        $this->children->removeElement($child);
    }

    /**
     * Get children
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getChildren() {
        return $this->children;
    }

    /**
     * Set user
     *
     * @param \AppBundle\Entity\User $user
     *
     * @return Decklist
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
     * Set lastPack
     *
     * @param \AppBundle\Entity\Pack $lastPack
     *
     * @return Decklist
     */
    public function setLastPack(\AppBundle\Entity\Pack $lastPack = null) {
        $this->lastPack = $lastPack;

        return $this;
    }

    /**
     * Get lastPack
     *
     * @return \AppBundle\Entity\Pack
     */
    public function getLastPack() {
        return $this->lastPack;
    }

    /**
     * Set parent
     *
     * @param \AppBundle\Entity\Deck $parent
     *
     * @return Decklist
     */
    public function setParent(\AppBundle\Entity\Deck $parent = null) {
        $this->parent = $parent;

        return $this;
    }

    /**
     * Get parent
     *
     * @return \AppBundle\Entity\Deck
     */
    public function getParent() {
        return $this->parent;
    }

    /**
     * Set precedent
     *
     * @param \AppBundle\Entity\Decklist $precedent
     *
     * @return Decklist
     */
    public function setPrecedent(\AppBundle\Entity\Decklist $precedent = null) {
        $this->precedent = $precedent;

        return $this;
    }

    /**
     * Get precedent
     *
     * @return \AppBundle\Entity\Decklist
     */
    public function getPrecedent() {
        return $this->precedent;
    }

    /**
     * Add favorite
     *
     * @param \AppBundle\Entity\User $favorite
     *
     * @return Decklist
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
     * @return Decklist
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
     * Set version
     *
     * @param string $version
     *
     * @return Decklist
     */
    public function setVersion($version) {
        $this->version = $version;

        return $this;
    }

    /**
     * Get version
     *
     * @return string
     */
    public function getVersion() {
        return $this->version;
    }

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $spheres;

    /**
     * Add sphere
     *
     * @param \AppBundle\Entity\Sphere $sphere
     *
     * @return Decklist
     */
    public function addSphere(\AppBundle\Entity\Sphere $sphere) {
        if (!$this->spheres->contains($sphere)) {
            $this->spheres[] = $sphere;
        }

        return $this;
    }

    /**
     * Remove sphere
     *
     * @param \AppBundle\Entity\Sphere $sphere
     */
    public function removeSphere(\AppBundle\Entity\Sphere $sphere) {
        $this->spheres->removeElement($sphere);
    }

    /**
     * Get spheres
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getSpheres() {
        return $this->spheres;
    }

    /**
     * @var \AppBundle\Entity\Sphere
     */
    private $predominantSphere;

    /**
     * Set predominantSphere
     *
     * @param \AppBundle\Entity\Sphere $predominantSphere
     *
     * @return Decklist
     */
    public function setPredominantSphere(\AppBundle\Entity\Sphere $predominantSphere = null) {
        $this->predominantSphere = $predominantSphere;

        return $this;
    }

    /**
     * Get predominantSphere
     *
     * @return \AppBundle\Entity\Sphere
     */
    public function getPredominantSphere() {
        return $this->predominantSphere;
    }

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $fellowships;

    /**
     * Add fellowship
     *
     * @param \AppBundle\Entity\FellowshipDeck $fellowship
     *
     * @return Decklist
     */
    public function addFellowship(\AppBundle\Entity\FellowshipDeck $fellowship) {
        $this->fellowships[] = $fellowship;

        return $this;
    }

    /**
     * Remove fellowship
     *
     * @param \AppBundle\Entity\FellowshipDeck $fellowship
     */
    public function removeFellowship(\AppBundle\Entity\FellowshipDeck $fellowship) {
        $this->fellowships->removeElement($fellowship);
    }

    /**
     * Get fellowships
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getFellowships() {
        return $this->fellowships;
    }

    /**
     * Get allFellowships
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getAllFellowships() {
        $allFellowships = $this->getFellowships()->toArray();

        return array_filter($allFellowships, function($k) {
            return $k->getFellowship()->getIsPublic();
        });
    }

    // Used to reduce json export data
    private $is_simple_export;

    public function setIsSimpleExport($is_simple_export) {
        $this->is_simple_export = $is_simple_export;

        return $this;
    }

    public function getIsSimpleExport() {
        return $this->is_simple_export;
    }

    /**
     * @var integer
     */
    private $startingThreat;

    /**
     * Set startingThreat
     *
     * @param integer $startingThreat
     *
     * @return Decklist
     */
    public function setStartingThreat($startingThreat) {
        $this->startingThreat = $startingThreat;

        return $this;
    }

    /**
     * Get startingThreat
     *
     * @return integer
     */
    public function getStartingThreat() {
        return $this->startingThreat;
    }
    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $questlogs;

    /**
     * Add questlog
     *
     * @param \AppBundle\Entity\QuestlogDeck $questlog
     *
     * @return Decklist
     */
    public function addQuestlog(\AppBundle\Entity\QuestlogDeck $questlog) {
        $this->questlogs[] = $questlog;

        return $this;
    }

    /**
     * Remove questlog
     *
     * @param \AppBundle\Entity\QuestlogDeck $questlog
     */
    public function removeQuestlog(\AppBundle\Entity\QuestlogDeck $questlog) {
        $this->questlogs->removeElement($questlog);
    }

    /**
     * Get questlogs
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getQuestlogs() {
        return $this->questlogs;
    }

    /**
     * Get allQuestlogs
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getAllQuestlogs() {
        $theseLogs = $this->getQuestlogs()->toArray();
        $parentLogs = [];
        if ($this->getParent()) $parentlogs = $this->getParent()->getQuestlogs()->toArray();
        $allQuestlogs = array_unique(array_merge($theseLogs, $parentLogs), SORT_REGULAR);
        return array_filter($allQuestlogs, function($k) {
            return $k->getQuestlog()->getIsPublic();
        });
    }
}
