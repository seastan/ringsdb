<?php

namespace App\Entity;

use App\Entity\Decklistsideslot;
use App\Entity\Decklistslot;
use App\Entity\FellowshipDecklist;
use App\Entity\QuestlogDeck;

class Decklist extends \App\Model\ExportableDeck implements \JsonSerializable {
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
     * @var \DateTime|null
     */
    private $dateLastComment;
    /**
     * @var string|null
     */
    private $descriptionMd;
    /**
     * @var string|null
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
     * @var bool|null
     */
    private $freezeComments;
    /**
     * @var string
     */
    private $version;
    /**
     * @var \Doctrine\Common\Collections\Collection<int, \App\Entity\Decklistslot>
     */
    private $slots;
    /**
     * @var \Doctrine\Common\Collections\Collection<int, \App\Entity\Decklistsideslot>
     */
    private $sideslots;
    /**
     * @var \Doctrine\Common\Collections\Collection<int, \App\Entity\Comment>
     */
    private $comments;
    /**
     * @var \Doctrine\Common\Collections\Collection<int, \App\Entity\Decklist>
     */
    private $successors;
    /**
     * @var \Doctrine\Common\Collections\Collection<int, \App\Entity\Deck>
     */
    private $children;
    /**
     * @var \App\Entity\User
     */
    private $user;
    /**
     * @var \App\Entity\Pack|null
     */
    private $lastPack;
    /**
     * @var \App\Entity\Deck|null
     */
    private $parent;
    /**
     * @var \App\Entity\Decklist|null
     */
    private $precedent;
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
     * @param \DateTime|null $dateLastComment
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
     * @return \DateTime|null
     */
    public function getDateLastComment() {
        return $this->dateLastComment;
    }

    /**
     * Set descriptionMd
     *
     * @param string|null $descriptionMd
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
     * @return Decklist
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
     * @param bool|null $freezeComments
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
     * @return bool|null
     */
    public function getFreezeComments() {
        return $this->freezeComments;
    }

    /**
     * Add slot
     *
     * @param \App\Entity\Decklistslot $slot
     *
     * @return Decklist
     */
    public function addSlot(\App\Entity\Decklistslot $slot) {
        $this->slots[] = $slot;

        return $this;
    }

    /**
     * Remove slot
     *
     * @param \App\Entity\Decklistslot $slot
     * @return void
     */
    public function removeSlot(\App\Entity\Decklistslot $slot) {
        $this->slots->removeElement($slot);
    }

    /**
     * Get slots
     *
     * @return \App\Model\SlotCollectionInterface<Decklistslot>
     */
    public function getSlots() {
        return new \App\Model\SlotCollectionDecorator($this->slots);
    }

    /**
     * Add sideslot
     *
     * @param \App\Entity\Decklistsideslot $sideslots
     *
     * @return Decklist
     */
    public function addSideslot(\App\Entity\Decklistsideslot $sideslots) {
        $this->sideslots[] = $sideslots;

        return $this;
    }

    /**
     * Remove sideslot
     *
     * @param \App\Entity\Decklistsideslot $sideslots
     * @return void
     */
    public function removeSideslot(\App\Entity\Decklistsideslot $sideslots) {
        $this->sideslots->removeElement($sideslots);
    }

    /**
     * Get slots
     *
     * @return \App\Model\SlotCollectionInterface<Decklistsideslot>
     */
    public function getSideslots() {
        return new \App\Model\SlotCollectionDecorator($this->sideslots);
    }

    /**
     * Add comment
     *
     * @param \App\Entity\Comment $comment
     *
     * @return Decklist
     */
    public function addComment(\App\Entity\Comment $comment) {
        $this->comments[] = $comment;

        return $this;
    }

    /**
     * Remove comment
     *
     * @param \App\Entity\Comment $comment
     * @return void
     */
    public function removeComment(\App\Entity\Comment $comment) {
        $this->comments->removeElement($comment);
    }

    /**
     * Get comments
     *
     * @return \Doctrine\Common\Collections\Collection<int, \App\Entity\Comment>
     */
    public function getComments() {
        return $this->comments;
    }

    /**
     * Add successor
     *
     * @param \App\Entity\Decklist $successor
     *
     * @return Decklist
     */
    public function addSuccessor(\App\Entity\Decklist $successor) {
        $this->successors[] = $successor;

        return $this;
    }

    /**
     * Remove successor
     *
     * @param \App\Entity\Decklist $successor
     * @return void
     */
    public function removeSuccessor(\App\Entity\Decklist $successor) {
        $this->successors->removeElement($successor);
    }

    /**
     * Get successors
     *
     * @return \Doctrine\Common\Collections\Collection<int, \App\Entity\Decklist>
     */
    public function getSuccessors() {
        return $this->successors;
    }

    /**
     * Add child
     *
     * @param \App\Entity\Deck $child
     *
     * @return Decklist
     */
    public function addChild(\App\Entity\Deck $child) {
        $this->children[] = $child;

        return $this;
    }

    /**
     * Remove child
     *
     * @param \App\Entity\Deck $child
     * @return void
     */
    public function removeChild(\App\Entity\Deck $child) {
        $this->children->removeElement($child);
    }

    /**
     * Get children
     *
     * @return \Doctrine\Common\Collections\Collection<int, \App\Entity\Deck>
     */
    public function getChildren() {
        return $this->children;
    }

    /**
     * Set user
     *
     * @param \App\Entity\User $user
     *
     * @return Decklist
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
     * Set lastPack
     *
     * @param \App\Entity\Pack $lastPack
     *
     * @return Decklist
     */
    public function setLastPack(\App\Entity\Pack $lastPack = null) {
        $this->lastPack = $lastPack;

        return $this;
    }

    /**
     * Get lastPack
     *
     * @return \App\Entity\Pack|null
     */
    public function getLastPack() {
        return $this->lastPack;
    }

    /**
     * Set parent
     *
     * @param \App\Entity\Deck $parent
     *
     * @return Decklist
     */
    public function setParent(\App\Entity\Deck $parent = null) {
        $this->parent = $parent;

        return $this;
    }

    /**
     * Get parent
     *
     * @return \App\Entity\Deck|null
     */
    public function getParent() {
        return $this->parent;
    }

    /**
     * Set precedent
     *
     * @param \App\Entity\Decklist $precedent
     *
     * @return Decklist
     */
    public function setPrecedent(\App\Entity\Decklist $precedent = null) {
        $this->precedent = $precedent;

        return $this;
    }

    /**
     * Get precedent
     *
     * @return \App\Entity\Decklist|null
     */
    public function getPrecedent() {
        return $this->precedent;
    }

    /**
     * Add favorite
     *
     * @param \App\Entity\User $favorite
     *
     * @return Decklist
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
     * @return Decklist
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
     * @var \Doctrine\Common\Collections\Collection<int, \App\Entity\Sphere>
     */
    private $spheres;

    /**
     * Add sphere
     *
     * @param \App\Entity\Sphere $sphere
     *
     * @return Decklist
     */
    public function addSphere(\App\Entity\Sphere $sphere) {
        if (!$this->spheres->contains($sphere)) {
            $this->spheres[] = $sphere;
        }

        return $this;
    }

    /**
     * Remove sphere
     *
     * @param \App\Entity\Sphere $sphere
     * @return void
     */
    public function removeSphere(\App\Entity\Sphere $sphere) {
        $this->spheres->removeElement($sphere);
    }

    /**
     * Get spheres
     *
     * @return \Doctrine\Common\Collections\Collection<int, \App\Entity\Sphere>
     */
    public function getSpheres() {
        return $this->spheres;
    }

    /**
     * @var \App\Entity\Sphere|null
     */
    private $predominantSphere;

    /**
     * Set predominantSphere
     *
     * @param \App\Entity\Sphere $predominantSphere
     *
     * @return Decklist
     */
    public function setPredominantSphere(\App\Entity\Sphere $predominantSphere = null) {
        $this->predominantSphere = $predominantSphere;

        return $this;
    }

    /**
     * Get predominantSphere
     *
     * @return \App\Entity\Sphere|null
     */
    public function getPredominantSphere() {
        return $this->predominantSphere;
    }

    /**
     * @var \Doctrine\Common\Collections\Collection<int, \App\Entity\FellowshipDecklist>
     */
    private $fellowships;

    /**
     * Add fellowship
     *
     * @param \App\Entity\FellowshipDecklist $fellowship
     *
     * @return Decklist
     */
    public function addFellowship(\App\Entity\FellowshipDecklist $fellowship) {
        $this->fellowships[] = $fellowship;

        return $this;
    }

    /**
     * Remove fellowship
     *
     * @param \App\Entity\FellowshipDecklist $fellowship
     * @return void
     */
    public function removeFellowship(\App\Entity\FellowshipDecklist $fellowship) {
        $this->fellowships->removeElement($fellowship);
    }

    /**
     * Get fellowships
     *
     * @return \Doctrine\Common\Collections\Collection<int, \App\Entity\FellowshipDecklist>
     */
    public function getFellowships() {
        return $this->fellowships;
    }

    /**
     * Get allFellowships
     *
     * @return array<int, FellowshipDecklist>
     */
    public function getAllFellowships() {
        $allFellowships = $this->getFellowships()->toArray();

        return array_filter($allFellowships, function($k) {
            return $k->getFellowship()->getIsPublic();
        });
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
     * @var \Doctrine\Common\Collections\Collection<int, \App\Entity\QuestlogDeck>
     */
    private $questlogs;

    /**
     * Add questlog
     *
     * @param \App\Entity\QuestlogDeck $questlog
     *
     * @return Decklist
     */
    public function addQuestlog(\App\Entity\QuestlogDeck $questlog) {
        $this->questlogs[] = $questlog;

        return $this;
    }

    /**
     * Remove questlog
     *
     * @param \App\Entity\QuestlogDeck $questlog
     * @return void
     */
    public function removeQuestlog(\App\Entity\QuestlogDeck $questlog) {
        $this->questlogs->removeElement($questlog);
    }

    /**
     * Get questlogs
     *
     * @return \Doctrine\Common\Collections\Collection<int, \App\Entity\QuestlogDeck>
     */
    public function getQuestlogs() {
        return $this->questlogs;
    }

    /**
     * Get allQuestlogs
     *
     * @return array<int, QuestlogDeck>
     */
    public function getAllQuestlogs() {
        $theseLogs = $this->getQuestlogs()->toArray();
        $parentLogs = [];
        if ($this->getParent()) $parentLogs = $this->getParent()->getQuestlogs()->toArray();
        $allQuestlogs = array_unique(array_merge($theseLogs, $parentLogs), SORT_REGULAR);
        return array_filter($allQuestlogs, function($k) {
            return $k->getQuestlog()->getIsPublic();
        });
    }

    /**
     * @return array{main: array<int|string, int>, side: array<int|string, int>}
     */
    public function getContent()
    {
        $content = [
            'main' => [],
            'side' => []
        ];

        foreach ($this->getSlots() as $slot) {
            $content['main'][$slot->getCard()->getCode()] = $slot->getQuantity();
        }

        foreach ($this->getSideslots() as $slot) {
            $content['side'][$slot->getCard()->getCode()] = $slot->getQuantity();
        }

        return $content;
    }
}
