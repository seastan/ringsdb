<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

/**
 * Fellowship.
 */
class Fellowship
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

    /**
     * @var string|null
     */
    private $descriptionMd;

    /**
     * @var string|null
     */
    private $descriptionHtml;

    /**
     * @var bool
     */
    private $isPublic;

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
     * @var Collection<int, FellowshipDeck>
     */
    private $decks;

    /**
     * @var Collection<int, FellowshipDecklist>
     */
    private $decklists;

    /**
     * @var Collection<int, FellowshipComment>
     */
    private $comments;

    /**
     * @var User
     */
    private $user;

    /**
     * @var Collection<int, User>
     */
    private $favorites;

    /**
     * @var Collection<int, User>
     */
    private $votes;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->decks = new ArrayCollection();
        $this->decklists = new ArrayCollection();
        $this->comments = new ArrayCollection();
        $this->favorites = new ArrayCollection();
        $this->votes = new ArrayCollection();
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
    public function setName($name): Fellowship
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
    public function setNameCanonical($nameCanonical): Fellowship
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
     * Set descriptionMd.
     *
     * @param string|null $descriptionMd
     */
    public function setDescriptionMd($descriptionMd): Fellowship
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
    public function setDescriptionHtml($descriptionHtml): Fellowship
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
     * Set isPublic.
     *
     * @param bool $isPublic
     */
    public function setIsPublic($isPublic): Fellowship
    {
        $this->isPublic = $isPublic;

        return $this;
    }

    /**
     * Get isPublic.
     */
    public function getIsPublic(): bool
    {
        return $this->isPublic;
    }

    /**
     * Set nbVotes.
     *
     * @param int $nbVotes
     */
    public function setNbVotes($nbVotes): Fellowship
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
    public function setNbFavorites($nbFavorites): Fellowship
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
    public function setNbComments($nbComments): Fellowship
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
     * Set dateCreation.
     *
     * @param \DateTime $dateCreation
     */
    public function setDateCreation($dateCreation): Fellowship
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
    public function setDateUpdate($dateUpdate): Fellowship
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
    public function setDateLastComment($dateLastComment): Fellowship
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
     * Add deck.
     */
    public function addDeck(FellowshipDeck $deck): Fellowship
    {
        $this->decks[] = $deck;

        return $this;
    }

    /**
     * Remove deck.
     */
    public function removeDeck(FellowshipDeck $deck): void
    {
        $this->decks->removeElement($deck);
    }

    /**
     * Get decks.
     *
     * @return Collection<int, FellowshipDeck>
     */
    public function getDecks(): Collection
    {
        return $this->decks;
    }

    /**
     * Add decklist.
     */
    public function addDecklist(FellowshipDecklist $decklist): Fellowship
    {
        $this->decklists[] = $decklist;

        return $this;
    }

    /**
     * Remove decklist.
     */
    public function removeDecklist(FellowshipDecklist $decklist): void
    {
        $this->decklists->removeElement($decklist);
    }

    /**
     * Get decklists.
     *
     * @return Collection<int, FellowshipDecklist>
     */
    public function getDecklists(): Collection
    {
        return $this->decklists;
    }

    /**
     * Add comment.
     */
    public function addComment(FellowshipComment $comment): Fellowship
    {
        $this->comments[] = $comment;

        return $this;
    }

    /**
     * Remove comment.
     */
    public function removeComment(FellowshipComment $comment): void
    {
        $this->comments->removeElement($comment);
    }

    /**
     * Get comments.
     *
     * @return Collection<int, FellowshipComment>
     */
    public function getComments(): Collection
    {
        return $this->comments;
    }

    /**
     * Set user.
     */
    public function setUser(User $user): Fellowship
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
     * Add favorite.
     */
    public function addFavorite(User $favorite): Fellowship
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
    public function addVote(User $vote): Fellowship
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
     * @var int
     */
    private $nbDecks;

    /**
     * Set nbDecks.
     *
     * @param int $nbDecks
     */
    public function setNbDecks($nbDecks): Fellowship
    {
        $this->nbDecks = $nbDecks;

        return $this;
    }

    /**
     * Get nbDecks.
     */
    public function getNbDecks(): int
    {
        return $this->nbDecks;
    }

    /**
     * @var \DateTime|null
     */
    private $datePublish;

    /**
     * Set datePublish.
     *
     * @param \DateTime|null $datePublish
     */
    public function setDatePublish($datePublish): Fellowship
    {
        $this->datePublish = $datePublish;

        return $this;
    }

    /**
     * Get datePublish.
     */
    public function getDatePublish(): ?\DateTime
    {
        return $this->datePublish;
    }
}
