<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

/**
 * Questlog.
 */
class Questlog
{
    /**
     * @var int|null
     */
    private $id;

    /**
     * @var string|null
     */
    private $name;

    /**
     * @var string|null
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
     * @var \DateTime|null
     */
    private $datePlayed;

    /**
     * @var string|null
     */
    private $questMode;

    /**
     * @var bool
     */
    private $success = false;

    /**
     * @var int|null
     */
    private $score;

    /**
     * @var int
     */
    private $nbDecks = 0;

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
     * @var bool
     */
    private $isPublic = false;

    private \DateTime $dateCreation;

    private \DateTime $dateUpdate;

    /**
     * @var Collection<int, QuestlogDeck>
     */
    private $decks;

    /**
     * @var Collection<int, QuestlogComment>
     */
    private $comments;

    private User $user;

    private ?Scenario $scenario = null;

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
    public function __construct(User $user)
    {
        $this->user = $user;
        $this->decks = new ArrayCollection();
        $this->comments = new ArrayCollection();
        $this->favorites = new ArrayCollection();
        $this->votes = new ArrayCollection();
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
    public function setName($name): Questlog
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name.
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * Set nameCanonical.
     *
     * @param string $nameCanonical
     */
    public function setNameCanonical($nameCanonical): Questlog
    {
        $this->nameCanonical = $nameCanonical;

        return $this;
    }

    /**
     * Get nameCanonical.
     */
    public function getNameCanonical(): ?string
    {
        return $this->nameCanonical;
    }

    /**
     * Set descriptionMd.
     *
     * @param string|null $descriptionMd
     */
    public function setDescriptionMd($descriptionMd): Questlog
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
    public function setDescriptionHtml($descriptionHtml): Questlog
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
     * Set datePlayed.
     *
     * @param \DateTime $datePlayed
     */
    public function setDatePlayed($datePlayed): Questlog
    {
        $this->datePlayed = $datePlayed;

        return $this;
    }

    /**
     * Get datePlayed.
     */
    public function getDatePlayed(): ?\DateTime
    {
        return $this->datePlayed;
    }

    /**
     * Set questMode.
     *
     * @param string $questMode
     */
    public function setQuestMode($questMode): Questlog
    {
        $this->questMode = $questMode;

        return $this;
    }

    /**
     * Get questMode.
     */
    public function getQuestMode(): ?string
    {
        return $this->questMode;
    }

    /**
     * Set success.
     *
     * @param bool $success
     */
    public function setSuccess($success): Questlog
    {
        $this->success = $success;

        return $this;
    }

    /**
     * Get success.
     */
    public function getSuccess(): bool
    {
        return $this->success;
    }

    /**
     * Set score.
     *
     * @param int|null $score
     */
    public function setScore($score): Questlog
    {
        $this->score = $score;

        return $this;
    }

    /**
     * Get score.
     */
    public function getScore(): ?int
    {
        return $this->score;
    }

    /**
     * Set nbDecks.
     *
     * @param int $nbDecks
     */
    public function setNbDecks($nbDecks): Questlog
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
     * Set nbVotes.
     *
     * @param int $nbVotes
     */
    public function setNbVotes($nbVotes): Questlog
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
    public function setNbFavorites($nbFavorites): Questlog
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
    public function setNbComments($nbComments): Questlog
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
     * Set isPublic.
     *
     * @param bool $isPublic
     */
    public function setIsPublic($isPublic): Questlog
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
     * Set dateCreation.
     *
     * @param \DateTime $dateCreation
     */
    public function setDateCreation($dateCreation): Questlog
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
    public function setDateUpdate($dateUpdate): Questlog
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
     * Add deck.
     */
    public function addDeck(QuestlogDeck $deck): Questlog
    {
        $this->decks[] = $deck;

        return $this;
    }

    /**
     * Remove deck.
     */
    public function removeDeck(QuestlogDeck $deck): void
    {
        $this->decks->removeElement($deck);
    }

    /**
     * Get decks.
     *
     * @return Collection<int, QuestlogDeck>
     */
    public function getDecks(): Collection
    {
        return $this->decks;
    }

    /**
     * Add comment.
     */
    public function addComment(QuestlogComment $comment): Questlog
    {
        $this->comments[] = $comment;

        return $this;
    }

    /**
     * Remove comment.
     */
    public function removeComment(QuestlogComment $comment): void
    {
        $this->comments->removeElement($comment);
    }

    /**
     * Get comments.
     *
     * @return Collection<int, QuestlogComment>
     */
    public function getComments(): Collection
    {
        return $this->comments;
    }

    /**
     * Set user.
     */
    public function setUser(User $user): Questlog
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
     * Set scenario.
     */
    public function setScenario(Scenario $scenario): Questlog
    {
        $this->scenario = $scenario;

        return $this;
    }

    /**
     * Get scenario.
     */
    public function getScenario(): ?Scenario
    {
        return $this->scenario;
    }

    /**
     * Add favorite.
     */
    public function addFavorite(User $favorite): Questlog
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
    public function addVote(User $vote): Questlog
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
     * @var \DateTime|null
     */
    private $datePublish;

    /**
     * Set datePublish.
     *
     * @param \DateTime|null $datePublish
     */
    public function setDatePublish($datePublish): Questlog
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
