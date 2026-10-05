<?php

declare(strict_types=1);

namespace App\Entity;

use App\Model\ExportableDeck;
use App\Model\SlotCollectionDecorator;
use App\Model\SlotCollectionInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

class Decklist extends ExportableDeck implements \JsonSerializable
{
    /**
     * @var int|null
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

    private \DateTime $dateCreation;

    private \DateTime $dateUpdate;

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
    private $nbVotes = 0;

    /**
     * @var int
     */
    private $nbFavorites = 0;

    /**
     * @var int
     */
    private $nbComments = 0;

    /**
     * @var bool|null
     */
    private $freezeComments;

    /**
     * @var string
     */
    private $version;

    /**
     * @var Collection<int, Decklistslot>
     */
    private $slots;

    /**
     * @var Collection<int, Decklistsideslot>
     */
    private $sideslots;

    /**
     * @var Collection<int, Comment>
     */
    private $comments;

    /**
     * @var Collection<int, Decklist>
     */
    private $successors;

    /**
     * @var Collection<int, Deck>
     */
    private $children;

    private User $user;

    private ?Pack $lastPack = null;

    private ?Deck $parent = null;

    private ?Decklist $precedent = null;

    /**
     * @var Collection<int, User>
     */
    private $favorites;

    /**
     * @var Collection<int, User>
     */
    private $votes;

    /**
     * @var Collection<int, QuestlogDeck>
     */
    private $questlogs;

    /**
     * @var int
     */
    private $startingThreat;

    /**
     * @var Collection<int, FellowshipDecklist>
     */
    private $fellowships;

    private ?Sphere $predominantSphere = null;

    /**
     * @var Collection<int, Sphere>
     */
    private $spheres;

    /**
     * Constructor.
     */
    public function __construct(User $user)
    {
        $this->user = $user;
        $this->slots = new ArrayCollection();
        $this->sideslots = new ArrayCollection();
        $this->comments = new ArrayCollection();
        $this->successors = new ArrayCollection();
        $this->children = new ArrayCollection();
        $this->favorites = new ArrayCollection();
        $this->votes = new ArrayCollection();
        $this->spheres = new ArrayCollection();
        $this->fellowships = new ArrayCollection();
        $this->questlogs = new ArrayCollection();
        $this->dateCreation = new \DateTime();
        $this->dateUpdate = new \DateTime();
    }

    /**
     * Get id.
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Set name.
     *
     * @param string $name
     */
    public function setName($name): Decklist
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name.
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Set nameCanonical.
     *
     * @param string $nameCanonical
     */
    public function setNameCanonical($nameCanonical): Decklist
    {
        $this->nameCanonical = $nameCanonical;

        return $this;
    }

    /**
     * Get nameCanonical.
     */
    public function getNameCanonical(): string
    {
        return $this->nameCanonical;
    }

    /**
     * Set dateCreation.
     *
     * @param \DateTime $dateCreation
     */
    public function setDateCreation($dateCreation): Decklist
    {
        $this->dateCreation = $dateCreation;

        return $this;
    }

    /**
     * Get dateCreation.
     */
    public function getDateCreation(): \DateTime
    {
        return $this->dateCreation;
    }

    /**
     * Set dateUpdate.
     *
     * @param \DateTime $dateUpdate
     */
    public function setDateUpdate($dateUpdate): Decklist
    {
        $this->dateUpdate = $dateUpdate;

        return $this;
    }

    /**
     * Get dateUpdate.
     */
    public function getDateUpdate(): \DateTime
    {
        return $this->dateUpdate;
    }

    /**
     * Set dateLastComment.
     *
     * @param \DateTime|null $dateLastComment
     */
    public function setDateLastComment($dateLastComment): Decklist
    {
        $this->dateLastComment = $dateLastComment;

        return $this;
    }

    /**
     * Get dateLastComment.
     */
    public function getDateLastComment(): ?\DateTime
    {
        return $this->dateLastComment;
    }

    /**
     * Set descriptionMd.
     *
     * @param string|null $descriptionMd
     */
    public function setDescriptionMd($descriptionMd): Decklist
    {
        $this->descriptionMd = $descriptionMd;

        return $this;
    }

    /**
     * Get descriptionMd.
     */
    public function getDescriptionMd(): ?string
    {
        return $this->descriptionMd;
    }

    /**
     * Set descriptionHtml.
     *
     * @param string|null $descriptionHtml
     */
    public function setDescriptionHtml($descriptionHtml): Decklist
    {
        $this->descriptionHtml = $descriptionHtml;

        return $this;
    }

    /**
     * Get descriptionHtml.
     */
    public function getDescriptionHtml(): ?string
    {
        return $this->descriptionHtml;
    }

    /**
     * Set signature.
     *
     * @param string $signature
     */
    public function setSignature($signature): Decklist
    {
        $this->signature = $signature;

        return $this;
    }

    /**
     * Get signature.
     */
    public function getSignature(): string
    {
        return $this->signature;
    }

    /**
     * Set nbVotes.
     *
     * @param int $nbVotes
     */
    public function setNbVotes($nbVotes): Decklist
    {
        $this->nbVotes = $nbVotes;

        return $this;
    }

    /**
     * Get nbVotes.
     */
    public function getNbVotes(): int
    {
        return $this->nbVotes;
    }

    /**
     * Set nbFavorites.
     *
     * @param int $nbFavorites
     */
    public function setNbFavorites($nbFavorites): Decklist
    {
        $this->nbFavorites = $nbFavorites;

        return $this;
    }

    /**
     * Get nbFavorites.
     */
    public function getNbFavorites(): int
    {
        return $this->nbFavorites;
    }

    /**
     * Set nbComments.
     *
     * @param int $nbComments
     */
    public function setNbComments($nbComments): Decklist
    {
        $this->nbComments = $nbComments;

        return $this;
    }

    /**
     * Get nbComments.
     */
    public function getNbComments(): int
    {
        return $this->nbComments;
    }

    /**
     * Set freezeComments.
     *
     * @param bool|null $freezeComments
     */
    public function setFreezeComments($freezeComments): Decklist
    {
        $this->freezeComments = $freezeComments;

        return $this;
    }

    /**
     * Get freezeComments.
     */
    public function getFreezeComments(): ?bool
    {
        return $this->freezeComments;
    }

    /**
     * Add slot.
     */
    public function addSlot(Decklistslot $slot): Decklist
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
     * @return SlotCollectionInterface<Decklistslot>
     */
    public function getSlots(): SlotCollectionInterface
    {
        return new SlotCollectionDecorator($this->slots);
    }

    /**
     * Add sideslot.
     */
    public function addSideslot(Decklistsideslot $sideslots): Decklist
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
     * @return SlotCollectionInterface<Decklistsideslot>
     */
    public function getSideslots(): SlotCollectionInterface
    {
        return new SlotCollectionDecorator($this->sideslots);
    }

    /**
     * Add comment.
     */
    public function addComment(Comment $comment): Decklist
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
     * @return Collection<int, Comment>
     */
    public function getComments(): Collection
    {
        return $this->comments;
    }

    /**
     * Add successor.
     */
    public function addSuccessor(Decklist $successor): Decklist
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
     * @return Collection<int, Decklist>
     */
    public function getSuccessors(): Collection
    {
        return $this->successors;
    }

    /**
     * Add child.
     */
    public function addChild(Deck $child): Decklist
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
     * @return Collection<int, Deck>
     */
    public function getChildren(): Collection
    {
        return $this->children;
    }

    /**
     * Set user.
     */
    public function setUser(User $user): Decklist
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Get user.
     */
    public function getUser(): User
    {
        return $this->user;
    }

    /**
     * Set lastPack.
     */
    public function setLastPack(?Pack $lastPack = null): Decklist
    {
        $this->lastPack = $lastPack;

        return $this;
    }

    /**
     * Get lastPack.
     */
    public function getLastPack(): ?Pack
    {
        return $this->lastPack;
    }

    /**
     * Set parent.
     */
    public function setParent(?Deck $parent = null): Decklist
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * Get parent.
     */
    public function getParent(): ?Deck
    {
        return $this->parent;
    }

    /**
     * Set precedent.
     */
    public function setPrecedent(?Decklist $precedent = null): Decklist
    {
        $this->precedent = $precedent;

        return $this;
    }

    /**
     * Get precedent.
     */
    public function getPrecedent(): ?Decklist
    {
        return $this->precedent;
    }

    /**
     * Add favorite.
     */
    public function addFavorite(User $favorite): Decklist
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
     * @return Collection<int, User>
     */
    public function getFavorites(): Collection
    {
        return $this->favorites;
    }

    /**
     * Add vote.
     */
    public function addVote(User $vote): Decklist
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
     * @return Collection<int, User>
     */
    public function getVotes(): Collection
    {
        return $this->votes;
    }

    /**
     * Set version.
     *
     * @param string $version
     */
    public function setVersion($version): Decklist
    {
        $this->version = $version;

        return $this;
    }

    /**
     * Get version.
     */
    public function getVersion(): string
    {
        return $this->version;
    }

    /**
     * Add sphere.
     */
    public function addSphere(Sphere $sphere): Decklist
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
     * @return Collection<int, Sphere>
     */
    public function getSpheres(): Collection
    {
        return $this->spheres;
    }

    /**
     * Set predominantSphere.
     */
    public function setPredominantSphere(?Sphere $predominantSphere = null): Decklist
    {
        $this->predominantSphere = $predominantSphere;

        return $this;
    }

    /**
     * Get predominantSphere.
     */
    public function getPredominantSphere(): ?Sphere
    {
        return $this->predominantSphere;
    }

    /**
     * Add fellowship.
     */
    public function addFellowship(FellowshipDecklist $fellowship): Decklist
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
     * @return Collection<int, FellowshipDecklist>
     */
    public function getFellowships(): Collection
    {
        return $this->fellowships;
    }

    /**
     * Get allFellowships.
     *
     * @return array<int, FellowshipDecklist>
     */
    public function getAllFellowships(): array
    {
        $allFellowships = $this->getFellowships()->toArray();

        return array_filter($allFellowships, fn (FellowshipDecklist $k): bool => $k->getFellowship()->getIsPublic());
    }

    /**
     * Set startingThreat.
     *
     * @param int $startingThreat
     */
    public function setStartingThreat($startingThreat): Decklist
    {
        $this->startingThreat = $startingThreat;

        return $this;
    }

    /**
     * Get startingThreat.
     */
    public function getStartingThreat(): int
    {
        return $this->startingThreat;
    }

    /**
     * Add questlog.
     */
    public function addQuestlog(QuestlogDeck $questlog): Decklist
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
     * @return Collection<int, QuestlogDeck>
     */
    public function getQuestlogs(): Collection
    {
        return $this->questlogs;
    }

    /**
     * Get allQuestlogs.
     *
     * @return array<int, QuestlogDeck>
     */
    public function getAllQuestlogs(): array
    {
        $theseLogs = $this->getQuestlogs()->toArray();
        $parentLogs = [];
        if ($this->getParent() instanceof Deck) {
            $parentLogs = $this->getParent()->getQuestlogs()->toArray();
        }

        $allQuestlogs = array_unique(array_merge($theseLogs, $parentLogs), SORT_REGULAR);

        return array_filter($allQuestlogs, fn (QuestlogDeck $k): bool => $k->getQuestlog()->getIsPublic());
    }

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
}
