<?php

declare(strict_types=1);

namespace App\Entity;

class Decklist extends \App\Model\ExportableDeck implements \JsonSerializable
{
    public function jsonSerialize()
    {
        $array = parent::getArrayExport();
        $array['is_published'] = true;
        $array['nb_votes'] = $this->getNbVotes();
        $array['nb_favorites'] = $this->getNbFavorites();
        $array['nb_comments'] = $this->getNbComments();
        $array['starting_threat'] = $this->getStartingThreat();

        return $array;
    }

    /**
     * @var int
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
     * @var int
     */
    private $nbVotes;
    /**
     * @var int
     */
    private $nbFavorites;
    /**
     * @var int
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
     * @var \Doctrine\Common\Collections\Collection<int, Decklistslot>
     */
    private $slots;
    /**
     * @var \Doctrine\Common\Collections\Collection<int, Decklistsideslot>
     */
    private $sideslots;
    /**
     * @var \Doctrine\Common\Collections\Collection<int, Comment>
     */
    private $comments;
    /**
     * @var \Doctrine\Common\Collections\Collection<int, Decklist>
     */
    private $successors;
    /**
     * @var \Doctrine\Common\Collections\Collection<int, Deck>
     */
    private $children;
    /**
     * @var User
     */
    private $user;
    /**
     * @var Pack|null
     */
    private $lastPack;
    /**
     * @var Deck|null
     */
    private $parent;
    /**
     * @var Decklist|null
     */
    private $precedent;
    /**
     * @var \Doctrine\Common\Collections\Collection<int, User>
     */
    private $favorites;
    /**
     * @var \Doctrine\Common\Collections\Collection<int, User>
     */
    private $votes;

    /**
     * Constructor.
     */
    public function __construct()
    {
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
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set name.
     *
     * @param string $name
     *
     * @return Decklist
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
     * Set nameCanonical.
     *
     * @param string $nameCanonical
     *
     * @return Decklist
     */
    public function setNameCanonical($nameCanonical)
    {
        $this->nameCanonical = $nameCanonical;

        return $this;
    }

    /**
     * Get nameCanonical.
     *
     * @return string
     */
    public function getNameCanonical()
    {
        return $this->nameCanonical;
    }

    /**
     * Set dateCreation.
     *
     * @param \DateTime $dateCreation
     *
     * @return Decklist
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
     * @return Decklist
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
     * Set dateLastComment.
     *
     * @param \DateTime|null $dateLastComment
     *
     * @return Decklist
     */
    public function setDateLastComment($dateLastComment)
    {
        $this->dateLastComment = $dateLastComment;

        return $this;
    }

    /**
     * Get dateLastComment.
     *
     * @return \DateTime|null
     */
    public function getDateLastComment()
    {
        return $this->dateLastComment;
    }

    /**
     * Set descriptionMd.
     *
     * @param string|null $descriptionMd
     *
     * @return Decklist
     */
    public function setDescriptionMd($descriptionMd)
    {
        $this->descriptionMd = $descriptionMd;

        return $this;
    }

    /**
     * Get descriptionMd.
     *
     * @return string|null
     */
    public function getDescriptionMd()
    {
        return $this->descriptionMd;
    }

    /**
     * Set descriptionHtml.
     *
     * @param string|null $descriptionHtml
     *
     * @return Decklist
     */
    public function setDescriptionHtml($descriptionHtml)
    {
        $this->descriptionHtml = $descriptionHtml;

        return $this;
    }

    /**
     * Get descriptionHtml.
     *
     * @return string|null
     */
    public function getDescriptionHtml()
    {
        return $this->descriptionHtml;
    }

    /**
     * Set signature.
     *
     * @param string $signature
     *
     * @return Decklist
     */
    public function setSignature($signature)
    {
        $this->signature = $signature;

        return $this;
    }

    /**
     * Get signature.
     *
     * @return string
     */
    public function getSignature()
    {
        return $this->signature;
    }

    /**
     * Set nbVotes.
     *
     * @param int $nbVotes
     *
     * @return Decklist
     */
    public function setNbVotes($nbVotes)
    {
        $this->nbVotes = $nbVotes;

        return $this;
    }

    /**
     * Get nbVotes.
     *
     * @return int
     */
    public function getNbVotes()
    {
        return $this->nbVotes;
    }

    /**
     * Set nbFavorites.
     *
     * @param int $nbFavorites
     *
     * @return Decklist
     */
    public function setNbFavorites($nbFavorites)
    {
        $this->nbFavorites = $nbFavorites;

        return $this;
    }

    /**
     * Get nbFavorites.
     *
     * @return int
     */
    public function getNbFavorites()
    {
        return $this->nbFavorites;
    }

    /**
     * Set nbComments.
     *
     * @param int $nbComments
     *
     * @return Decklist
     */
    public function setNbComments($nbComments)
    {
        $this->nbComments = $nbComments;

        return $this;
    }

    /**
     * Get nbComments.
     *
     * @return int
     */
    public function getNbComments()
    {
        return $this->nbComments;
    }

    /**
     * Set freezeComments.
     *
     * @param bool|null $freezeComments
     *
     * @return Decklist
     */
    public function setFreezeComments($freezeComments)
    {
        $this->freezeComments = $freezeComments;

        return $this;
    }

    /**
     * Get freezeComments.
     *
     * @return bool|null
     */
    public function getFreezeComments()
    {
        return $this->freezeComments;
    }

    /**
     * Add slot.
     *
     * @return Decklist
     */
    public function addSlot(Decklistslot $slot)
    {
        $this->slots[] = $slot;

        return $this;
    }

    /**
     * Remove slot.
     */
    public function removeSlot(Decklistslot $slot): void
    {
        $this->slots->removeElement($slot);
    }

    /**
     * Get slots.
     *
     * @return \App\Model\SlotCollectionInterface<Decklistslot>
     */
    public function getSlots()
    {
        return new \App\Model\SlotCollectionDecorator($this->slots);
    }

    /**
     * Add sideslot.
     *
     * @return Decklist
     */
    public function addSideslot(Decklistsideslot $sideslots)
    {
        $this->sideslots[] = $sideslots;

        return $this;
    }

    /**
     * Remove sideslot.
     */
    public function removeSideslot(Decklistsideslot $sideslots): void
    {
        $this->sideslots->removeElement($sideslots);
    }

    /**
     * Get slots.
     *
     * @return \App\Model\SlotCollectionInterface<Decklistsideslot>
     */
    public function getSideslots()
    {
        return new \App\Model\SlotCollectionDecorator($this->sideslots);
    }

    /**
     * Add comment.
     *
     * @return Decklist
     */
    public function addComment(Comment $comment)
    {
        $this->comments[] = $comment;

        return $this;
    }

    /**
     * Remove comment.
     */
    public function removeComment(Comment $comment): void
    {
        $this->comments->removeElement($comment);
    }

    /**
     * Get comments.
     *
     * @return \Doctrine\Common\Collections\Collection<int, Comment>
     */
    public function getComments()
    {
        return $this->comments;
    }

    /**
     * Add successor.
     *
     * @return Decklist
     */
    public function addSuccessor(Decklist $successor)
    {
        $this->successors[] = $successor;

        return $this;
    }

    /**
     * Remove successor.
     */
    public function removeSuccessor(Decklist $successor): void
    {
        $this->successors->removeElement($successor);
    }

    /**
     * Get successors.
     *
     * @return \Doctrine\Common\Collections\Collection<int, Decklist>
     */
    public function getSuccessors()
    {
        return $this->successors;
    }

    /**
     * Add child.
     *
     * @return Decklist
     */
    public function addChild(Deck $child)
    {
        $this->children[] = $child;

        return $this;
    }

    /**
     * Remove child.
     */
    public function removeChild(Deck $child): void
    {
        $this->children->removeElement($child);
    }

    /**
     * Get children.
     *
     * @return \Doctrine\Common\Collections\Collection<int, Deck>
     */
    public function getChildren()
    {
        return $this->children;
    }

    /**
     * Set user.
     *
     * @return Decklist
     */
    public function setUser(User $user)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Get user.
     *
     * @return User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Set lastPack.
     *
     * @return Decklist
     */
    public function setLastPack(?Pack $lastPack = null)
    {
        $this->lastPack = $lastPack;

        return $this;
    }

    /**
     * Get lastPack.
     *
     * @return Pack|null
     */
    public function getLastPack()
    {
        return $this->lastPack;
    }

    /**
     * Set parent.
     *
     * @return Decklist
     */
    public function setParent(?Deck $parent = null)
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * Get parent.
     *
     * @return Deck|null
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * Set precedent.
     *
     * @return Decklist
     */
    public function setPrecedent(?Decklist $precedent = null)
    {
        $this->precedent = $precedent;

        return $this;
    }

    /**
     * Get precedent.
     *
     * @return Decklist|null
     */
    public function getPrecedent()
    {
        return $this->precedent;
    }

    /**
     * Add favorite.
     *
     * @return Decklist
     */
    public function addFavorite(User $favorite)
    {
        $this->favorites[] = $favorite;

        return $this;
    }

    /**
     * Remove favorite.
     */
    public function removeFavorite(User $favorite): void
    {
        $this->favorites->removeElement($favorite);
    }

    /**
     * Get favorites.
     *
     * @return \Doctrine\Common\Collections\Collection<int, User>
     */
    public function getFavorites()
    {
        return $this->favorites;
    }

    /**
     * Add vote.
     *
     * @return Decklist
     */
    public function addVote(User $vote)
    {
        $this->votes[] = $vote;

        return $this;
    }

    /**
     * Remove vote.
     */
    public function removeVote(User $vote): void
    {
        $this->votes->removeElement($vote);
    }

    /**
     * Get votes.
     *
     * @return \Doctrine\Common\Collections\Collection<int, User>
     */
    public function getVotes()
    {
        return $this->votes;
    }

    /**
     * Set version.
     *
     * @param string $version
     *
     * @return Decklist
     */
    public function setVersion($version)
    {
        $this->version = $version;

        return $this;
    }

    /**
     * Get version.
     *
     * @return string
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * @var \Doctrine\Common\Collections\Collection<int, Sphere>
     */
    private $spheres;

    /**
     * Add sphere.
     *
     * @return Decklist
     */
    public function addSphere(Sphere $sphere)
    {
        if (!$this->spheres->contains($sphere)) {
            $this->spheres[] = $sphere;
        }

        return $this;
    }

    /**
     * Remove sphere.
     */
    public function removeSphere(Sphere $sphere): void
    {
        $this->spheres->removeElement($sphere);
    }

    /**
     * Get spheres.
     *
     * @return \Doctrine\Common\Collections\Collection<int, Sphere>
     */
    public function getSpheres()
    {
        return $this->spheres;
    }

    /**
     * @var Sphere|null
     */
    private $predominantSphere;

    /**
     * Set predominantSphere.
     *
     * @return Decklist
     */
    public function setPredominantSphere(?Sphere $predominantSphere = null)
    {
        $this->predominantSphere = $predominantSphere;

        return $this;
    }

    /**
     * Get predominantSphere.
     *
     * @return Sphere|null
     */
    public function getPredominantSphere()
    {
        return $this->predominantSphere;
    }

    /**
     * @var \Doctrine\Common\Collections\Collection<int, FellowshipDecklist>
     */
    private $fellowships;

    /**
     * Add fellowship.
     *
     * @return Decklist
     */
    public function addFellowship(FellowshipDecklist $fellowship)
    {
        $this->fellowships[] = $fellowship;

        return $this;
    }

    /**
     * Remove fellowship.
     */
    public function removeFellowship(FellowshipDecklist $fellowship): void
    {
        $this->fellowships->removeElement($fellowship);
    }

    /**
     * Get fellowships.
     *
     * @return \Doctrine\Common\Collections\Collection<int, FellowshipDecklist>
     */
    public function getFellowships()
    {
        return $this->fellowships;
    }

    /**
     * Get allFellowships.
     *
     * @return array<int, FellowshipDecklist>
     */
    public function getAllFellowships()
    {
        $allFellowships = $this->getFellowships()->toArray();

        return array_filter($allFellowships, fn ($k) => $k->getFellowship()->getIsPublic());
    }

    /**
     * @var int
     */
    private $startingThreat;

    /**
     * Set startingThreat.
     *
     * @param int $startingThreat
     *
     * @return Decklist
     */
    public function setStartingThreat($startingThreat)
    {
        $this->startingThreat = $startingThreat;

        return $this;
    }

    /**
     * Get startingThreat.
     *
     * @return int
     */
    public function getStartingThreat()
    {
        return $this->startingThreat;
    }
    /**
     * @var \Doctrine\Common\Collections\Collection<int, QuestlogDeck>
     */
    private $questlogs;

    /**
     * Add questlog.
     *
     * @return Decklist
     */
    public function addQuestlog(QuestlogDeck $questlog)
    {
        $this->questlogs[] = $questlog;

        return $this;
    }

    /**
     * Remove questlog.
     */
    public function removeQuestlog(QuestlogDeck $questlog): void
    {
        $this->questlogs->removeElement($questlog);
    }

    /**
     * Get questlogs.
     *
     * @return \Doctrine\Common\Collections\Collection<int, QuestlogDeck>
     */
    public function getQuestlogs()
    {
        return $this->questlogs;
    }

    /**
     * Get allQuestlogs.
     *
     * @return array<int, QuestlogDeck>
     */
    public function getAllQuestlogs()
    {
        $theseLogs = $this->getQuestlogs()->toArray();
        $parentLogs = [];
        if ($this->getParent()) {
            $parentLogs = $this->getParent()->getQuestlogs()->toArray();
        }
        $allQuestlogs = array_unique(array_merge($theseLogs, $parentLogs), SORT_REGULAR);

        return array_filter($allQuestlogs, fn ($k) => $k->getQuestlog()->getIsPublic());
    }

    /**
     * @return array{main: array<int|string, int>, side: array<int|string, int>}
     */
    public function getContent()
    {
        $content = [
            'main' => [],
            'side' => [],
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
